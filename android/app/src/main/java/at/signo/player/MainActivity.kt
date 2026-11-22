package at.signo.player

import android.annotation.SuppressLint
import android.app.Activity
import android.content.Intent
import android.graphics.Bitmap
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import android.view.KeyEvent
import android.view.View
import android.view.WindowManager
import android.webkit.RenderProcessGoneDetail
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import org.json.JSONObject
import java.util.Calendar

class MainActivity : Activity() {

    private lateinit var web: WebView
    private var wakeLock: PowerManager.WakeLock? = null
    private val ui = Handler(Looper.getMainLooper())

    private var lastLoadOk = 0L
    private var bootAt = System.currentTimeMillis()
    private var reloadScheduled = false
    private var consecutiveErrors = 0

    companion object {
        @Volatile var instance: MainActivity? = null

        /** Nach so vielen Stunden Dauerbetrieb wird nachts neu gestartet. */
        private const val MIN_UPTIME_FOR_NIGHTLY_MS = 6L * 60 * 60 * 1000

        /** Ohne erfolgreiches Laden nach dieser Zeit -> Vollneustart. */
        private const val DEAD_AFTER_MS = 20L * 60 * 1000
    }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        instance = this

        // Abstürze abfangen und automatisch neu starten
        Thread.setDefaultUncaughtExceptionHandler(
            CrashHandler(applicationContext, Thread.getDefaultUncaughtExceptionHandler())
        )

        window.addFlags(
            WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON or
            WindowManager.LayoutParams.FLAG_DISMISS_KEYGUARD or
            WindowManager.LayoutParams.FLAG_TURN_SCREEN_ON
        )

        web = WebView(this)
        setContentView(web)
        hideSystemUi()
        configureWebView()

        Lockdown.apply(this)   // wirkt nur, falls Device Owner aktiv ist
        acquireWakeLock()
        startCommandService()
        KeepAliveReceiver.schedule(this)
        requestBatteryExemption()
        bootstrap()
        startWatchdog()
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun configureWebView() {
        with(web.settings) {
            userAgentString = userAgentString + " SignoPlayer/" + BuildConfig.VERSION_CODE
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            mediaPlaybackRequiresUserGesture = false
            cacheMode = WebSettings.LOAD_DEFAULT
            loadWithOverviewMode = true
            useWideViewPort = true
            allowFileAccess = true
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
            }
        }

        web.webChromeClient = WebChromeClient()
        web.webViewClient = object : WebViewClient() {

            override fun onPageFinished(view: WebView?, url: String?) {
                lastLoadOk = System.currentTimeMillis()
                consecutiveErrors = 0
            }

            override fun onReceivedError(
                view: WebView?, request: WebResourceRequest?, error: WebResourceError?
            ) {
                if (request?.isForMainFrame == true) {
                    consecutiveErrors++
                    // Wartezeit wächst: 10 s, 20 s, 30 s … höchstens 2 Minuten
                    val wait = minOf(120_000L, 10_000L * consecutiveErrors)
                    scheduleReload(wait)
                }
            }

            /** Der Anzeigeprozess ist abgestürzt – häufigste Ursache für schwarze Bildschirme. */
            override fun onRenderProcessGone(
                view: WebView?, detail: RenderProcessGoneDetail?
            ): Boolean {
                AppRestarter.restartNow(this@MainActivity)
                return true
            }
        }
    }

    private fun bootstrap() {
        Thread {
            var token = Prefs.token(this)
            if (token.isNullOrBlank()) {
                val body = JSONObject()
                    .put("device_id", Prefs.deviceId(this))
                    .put("resolution", "${resources.displayMetrics.widthPixels}x${resources.displayMetrics.heightPixels}")
                val res = Api.postJson("${BuildConfig.BASE_URL}/api/player/register", body)
                if (res != null) {
                    try {
                        val t = JSONObject(res).optString("token", "")
                        if (t.isNotBlank()) { Prefs.setToken(this, t); token = t }
                    } catch (e: Exception) {}
                }
            }
            val dev = java.net.URLEncoder.encode(Prefs.deviceId(this), "UTF-8")
            val url = if (token.isNullOrBlank()) "${BuildConfig.BASE_URL}/tv?device=$dev"
                      else "${BuildConfig.BASE_URL}/tv?token=$token&device=$dev"
            ui.post { web.loadUrl(url) }
        }.start()
    }

    private fun scheduleReload(delayMs: Long) {
        if (reloadScheduled) return
        reloadScheduled = true
        ui.postDelayed({
            reloadScheduled = false
            try { web.reload() } catch (e: Exception) {}
        }, delayMs)
    }

    /**
     * Überwachung, die jede Minute prüft:
     *  - hängt die Seite zu lange? -> Vollneustart
     *  - läuft die App seit Tagen? -> nachts um 03:30 neu starten
     */
    private fun startWatchdog() {
        ui.postDelayed(object : Runnable {
            override fun run() {
                try { check() } catch (e: Exception) {}
                ui.postDelayed(this, 60_000)
            }
        }, 60_000)
    }

    private fun check() {
        val now = System.currentTimeMillis()

        // Seite seit langer Zeit nicht mehr erfolgreich geladen
        if (lastLoadOk > 0 && (now - lastLoadOk) > DEAD_AFTER_MS) {
            AppRestarter.restartNow(this)
            return
        }

        // Nie erfolgreich geladen, App läuft aber schon lange
        if (lastLoadOk == 0L && (now - bootAt) > DEAD_AFTER_MS) {
            AppRestarter.restartNow(this)
            return
        }

        // Nächtlicher Neustart bei Dauerbetrieb
        val cal = Calendar.getInstance()
        val hour = cal.get(Calendar.HOUR_OF_DAY)
        val minute = cal.get(Calendar.MINUTE)
        if (hour == 3 && minute in 30..34 && (now - bootAt) > MIN_UPTIME_FOR_NIGHTLY_MS) {
            AppRestarter.restartNow(this)
        }
    }


    /** Bittet einmalig darum, von der Akku-Optimierung ausgenommen zu werden. */
    private fun requestBatteryExemption() {
        try {
            if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) return
            val pm = getSystemService(POWER_SERVICE) as android.os.PowerManager
            if (pm.isIgnoringBatteryOptimizations(packageName)) return

            val i = Intent(android.provider.Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS)
            i.data = android.net.Uri.parse("package:$packageName")
            i.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            startActivity(i)
        } catch (e: Exception) {}
    }

    private fun startCommandService() {
        try {
            val i = Intent(this, CommandService::class.java)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) startForegroundService(i) else startService(i)
        } catch (e: Exception) {}
    }

    private fun acquireWakeLock() {
        try {
            val pm = getSystemService(POWER_SERVICE) as PowerManager
            @Suppress("DEPRECATION")
            wakeLock = pm.newWakeLock(
                PowerManager.SCREEN_BRIGHT_WAKE_LOCK or PowerManager.ACQUIRE_CAUSES_WAKEUP,
                "signo:player"
            )
            wakeLock?.acquire()
        } catch (e: Exception) {}
    }

    private fun hideSystemUi() {
        @Suppress("DEPRECATION")
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                or View.SYSTEM_UI_FLAG_FULLSCREEN
                or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
                or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
        )
    }

    override fun onResume() {
        super.onResume()
        hideSystemUi()
        if (Lockdown.isOwner(this)) {
            try { startLockTask() } catch (e: Exception) {}
        }
    }

    override fun onWindowFocusChanged(hasFocus: Boolean) {
        super.onWindowFocusChanged(hasFocus)
        if (hasFocus) hideSystemUi()
    }

    /** Zurück-Taste im Kiosk deaktivieren. */
    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_BACK) return true
        return super.onKeyDown(keyCode, event)
    }

    /**
     * Nimmt den tatsächlich angezeigten Bildinhalt auf.
     * Nutzt PixelCopy, weil eine hardwarebeschleunigte WebView sonst schwarz bleibt.
     */
    fun captureScreen(): ByteArray? {
        val w = if (web.width > 0) web.width else window.decorView.width
        val h = if (web.height > 0) web.height else window.decorView.height
        if (w <= 0 || h <= 0) return null

        var bmp: Bitmap? = null

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            try {
                val target = Bitmap.createBitmap(w, h, Bitmap.Config.ARGB_8888)
                val done = java.util.concurrent.CountDownLatch(1)
                var ok = false

                val loc = IntArray(2)
                web.getLocationInWindow(loc)
                val rect = android.graphics.Rect(loc[0], loc[1], loc[0] + w, loc[1] + h)

                android.view.PixelCopy.request(window, rect, target, { result ->
                    ok = (result == android.view.PixelCopy.SUCCESS)
                    done.countDown()
                }, Handler(Looper.getMainLooper()))

                done.await(4, java.util.concurrent.TimeUnit.SECONDS)
                if (ok) { bmp = target } else { target.recycle() }
            } catch (e: Exception) {}
        }

        // Rückfallweg für ältere Geräte
        if (bmp == null) {
            try {
                val soft = Bitmap.createBitmap(w, h, Bitmap.Config.ARGB_8888)
                web.draw(android.graphics.Canvas(soft))
                bmp = soft
            } catch (e: Exception) { return null }
        }

        return try {
            // Auf sinnvolle Größe verkleinern
            val maxW = 800
            val use = if (bmp!!.width > maxW) {
                val nh = bmp!!.height * maxW / bmp!!.width
                Bitmap.createScaledBitmap(bmp!!, maxW, nh, true)
            } else bmp!!

            val out = java.io.ByteArrayOutputStream()
            use.compress(Bitmap.CompressFormat.JPEG, 70, out)
            if (use !== bmp) { use.recycle() }
            bmp!!.recycle()
            out.toByteArray()
        } catch (e: Exception) { null }
    }

    fun reloadPage() { ui.post { try { web.reload() } catch (e: Exception) {} } }

    fun restartApp() { AppRestarter.restartNow(this) }

    override fun onDestroy() {
        try { wakeLock?.release() } catch (e: Exception) {}
        if (instance === this) instance = null
        super.onDestroy()
    }
}
