package at.signo.player

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build

/**
 * Wird regelmäßig vom Wecker aufgerufen und prüft, ob der Player noch läuft.
 * Falls nicht, wird er neu gestartet.
 */
class KeepAliveReceiver : BroadcastReceiver() {

    companion object {
        private const val REQUEST_CODE = 8123
        private const val INTERVAL_MS = 5L * 60 * 1000

        fun schedule(ctx: Context) {
            try {
                val intent = Intent(ctx, KeepAliveReceiver::class.java)
                var flags = PendingIntent.FLAG_UPDATE_CURRENT
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                    flags = flags or PendingIntent.FLAG_IMMUTABLE
                }
                val pi = PendingIntent.getBroadcast(ctx, REQUEST_CODE, intent, flags)
                val am = ctx.getSystemService(Context.ALARM_SERVICE) as AlarmManager

                am.setRepeating(
                    AlarmManager.RTC_WAKEUP,
                    System.currentTimeMillis() + INTERVAL_MS,
                    INTERVAL_MS,
                    pi
                )
            } catch (e: Exception) {}
        }
    }

    override fun onReceive(context: Context, intent: Intent?) {
        // Dienst sicherstellen
        try {
            val svc = Intent(context, CommandService::class.java)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(svc)
            } else {
                context.startService(svc)
            }
        } catch (e: Exception) {}

        // Player wieder starten, falls er nicht mehr läuft
        if (MainActivity.instance == null) {
            try {
                val launch = Intent(context, MainActivity::class.java)
                    .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP)
                context.startActivity(launch)
            } catch (e: Exception) {
                AppRestarter.scheduleRestart(context, 3000)
            }
        }
    }
}
