package at.signo.player

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.BatteryManager
import android.os.Build
import android.os.Environment
import android.os.IBinder
import android.os.StatFs
import org.json.JSONArray
import org.json.JSONObject

/**
 * Holt Befehle vom Server und führt sie aus.
 * Unterstützt: reload, refresh, identify, reboot, restart_app, screenshot,
 *              update, wipe, screen_off, brightness:NN, volume:NN
 */
class CommandService : Service() {

    private var running = false
    private var failures = 0

    /** Holt den aktuellen Zugang für dieses Gerät (gleiche Geräte-ID = gleicher Bildschirm). */
    private fun resync(base: String) {
        val body = JSONObject()
            .put("device_id", Prefs.deviceId(this))
            .put("resolution", "${resources.displayMetrics.widthPixels}x${resources.displayMetrics.heightPixels}")
        val res = Api.postJson("$base/api/player/register", body) ?: return
        try {
            val t = JSONObject(res).optString("token", "")
            if (t.isNotBlank() && t != Prefs.token(this)) Prefs.setToken(this, t)
        } catch (e: Exception) {}
    }
    private val intervalMs = 30_000L

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        startForegroundNotification()
        if (!running) {
            running = true
            Thread { loop() }.start()
        }
        return START_STICKY
    }

    private fun loop() {
        while (running) {
            try { poll() } catch (e: Exception) {}
            try { Thread.sleep(intervalMs) } catch (e: InterruptedException) { return }
        }
    }

    private fun poll() {
        val token = Prefs.token(this) ?: return
        val base = BuildConfig.BASE_URL

        val q = "?v=" + BuildConfig.VERSION_CODE +
                "&owner=" + (if (Lockdown.isOwner(this)) "1" else "0") +
                "&batt=" + batteryPercent() +
                "&free=" + freeMb()

        val res = Api.get("$base/api/player/$token/agent$q")
        if (res == null) {
            // Server nicht erreichbar ODER Bildschirm neu gekoppelt: gelegentlich Kennung abgleichen
            failures++
            if (failures % 10 == 0) resync(base)
            return
        }
        failures = 0
        val obj = try { JSONObject(res) } catch (e: Exception) { return }
        val cmds: JSONArray = obj.optJSONArray("commands") ?: return
        if (cmds.length() == 0) return

        // Befehle, die den Prozess beenden: ZUERST bestaetigen, sonst Endlosschleife.
        val selfTerminating = setOf("restart_app", "reboot", "update", "wipe")

        val deferred = ArrayList<String>()
        val ackIds = JSONArray()
        val normal = ArrayList<Pair<Int, String>>()

        for (i in 0 until cmds.length()) {
            val c = cmds.optJSONObject(i) ?: continue
            val id = c.optInt("id", 0)
            val name = c.optString("command", "")
            if (selfTerminating.contains(name.split(":")[0])) {
                ackIds.put(id)
                deferred.add(name)
            } else {
                normal.add(Pair(id, name))
            }
        }

        // 1) Selbstbeendende Befehle vorab quittieren
        if (ackIds.length() > 0) {
            Api.postJson("$base/api/player/$token/ack", JSONObject().put("ids", ackIds))
        }

        // 2) Normale Befehle ausfuehren und danach quittieren
        val done = JSONArray()
        for ((id, name) in normal) {
            if (execute(name)) done.put(id)
        }
        if (done.length() > 0) {
            Api.postJson("$base/api/player/$token/ack", JSONObject().put("ids", done))
        }

        // 3) Erst jetzt die selbstbeendenden Befehle ausloesen
        for (name in deferred) {
            try { Thread.sleep(1500) } catch (e: InterruptedException) {}
            execute(name)
        }
    }

    private fun execute(raw: String): Boolean {
        val act = MainActivity.instance
        val parts = raw.split(":", limit = 2)
        val cmd = parts[0]
        val arg = parts.getOrNull(1)?.toIntOrNull() ?: 0

        return when (cmd) {
            "reload", "refresh" -> { act?.reloadPage(); true }
            "identify"    -> true
            "restart_app" -> { act?.restartApp(); true }
            "reboot"      -> Lockdown.reboot(this)
            "screenshot"  -> sendScreenshot()
            "update"      -> Updater.updateFromServer(this)
            "screen_off"  -> Lockdown.screenOff(this)
            "brightness"  -> Lockdown.brightness(this, arg)
            "volume"      -> Lockdown.volume(this, arg)
            "wipe"        -> Lockdown.wipe(this)
            "unlock"      -> Lockdown.releaseOwner(this)
            else          -> true
        }
    }

    private fun sendScreenshot(): Boolean {
        val act = MainActivity.instance ?: return false
        val token = Prefs.token(this) ?: return false
        var data: ByteArray? = null

        val lock = Object()
        act.runOnUiThread {
            data = act.captureScreen()
            synchronized(lock) { lock.notifyAll() }
        }
        synchronized(lock) { try { lock.wait(12000) } catch (e: InterruptedException) {} }

        val bytes = data ?: return false
        return Api.postBytes(
            "${BuildConfig.BASE_URL}/api/player/$token/screenshot",
            "screenshot", "screen.jpg", bytes, "image/jpeg"
        )
    }

    private fun batteryPercent(): Int = try {
        val bm = getSystemService(Context.BATTERY_SERVICE) as BatteryManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
            bm.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY) else -1
    } catch (e: Exception) { -1 }

    private fun freeMb(): Long = try {
        val st = StatFs(Environment.getDataDirectory().path)
        st.availableBytes / (1024 * 1024)
    } catch (e: Exception) { -1 }

    private fun startForegroundNotification() {
        val channelId = "signo_player"
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val nm = getSystemService(NotificationManager::class.java)
            nm.createNotificationChannel(
                NotificationChannel(channelId, "Player", NotificationManager.IMPORTANCE_MIN)
                    .apply { setShowBadge(false) }
            )
        }
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O)
            Notification.Builder(this, channelId) else Notification.Builder(this)

        val n = builder
            .setContentTitle(getString(R.string.app_name))
            .setContentText(getString(R.string.service_running))
            .setSmallIcon(android.R.drawable.stat_notify_sync)
            .build()
        startForeground(1, n)
    }


    /** Wird die App aus der Übersicht entfernt, sofort wieder starten. */
    override fun onTaskRemoved(rootIntent: Intent?) {
        AppRestarter.scheduleRestart(this, 2000)
        super.onTaskRemoved(rootIntent)
    }

    override fun onDestroy() {
        running = false
        super.onDestroy()
    }
}
