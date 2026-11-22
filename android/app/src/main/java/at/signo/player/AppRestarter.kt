package at.signo.player

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build

/**
 * Startet die App vollständig neu (neuer Prozess).
 * Räumt damit Speicherlecks und hängende WebViews zuverlässig auf.
 */
object AppRestarter {

    private const val REQUEST_CODE = 4711

    fun scheduleRestart(ctx: Context, delayMs: Long) {
        try {
            val intent = Intent(ctx, MainActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)

            var flags = PendingIntent.FLAG_CANCEL_CURRENT
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                flags = flags or PendingIntent.FLAG_IMMUTABLE
            }

            val pi = PendingIntent.getActivity(ctx, REQUEST_CODE, intent, flags)
            val am = ctx.getSystemService(Context.ALARM_SERVICE) as AlarmManager
            am.set(AlarmManager.RTC, System.currentTimeMillis() + delayMs, pi)
        } catch (e: Exception) {}
    }

    /** Sofort neu starten. */
    fun restartNow(ctx: Context) {
        scheduleRestart(ctx, 1500)
        Runtime.getRuntime().exit(0)
    }
}
