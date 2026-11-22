package at.signo.player

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

/**
 * Startet den Player nach dem Einschalten des Geräts
 * und nach einer App-Aktualisierung automatisch.
 */
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        try {
            val launch = Intent(context, MainActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP)
            context.startActivity(launch)
        } catch (e: Exception) {
            AppRestarter.scheduleRestart(context, 5000)
        }
    }
}
