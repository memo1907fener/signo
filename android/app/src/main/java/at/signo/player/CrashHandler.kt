package at.signo.player

import android.content.Context

/**
 * Fängt unbehandelte Abstürze ab und startet die App danach automatisch neu,
 * damit der Bildschirm nie dauerhaft schwarz bleibt.
 */
class CrashHandler(
    private val ctx: Context,
    private val previous: Thread.UncaughtExceptionHandler?
) : Thread.UncaughtExceptionHandler {

    override fun uncaughtException(t: Thread, e: Throwable) {
        try {
            AppRestarter.scheduleRestart(ctx, 3000)
        } catch (ignored: Exception) {}

        try {
            previous?.uncaughtException(t, e)
        } catch (ignored: Exception) {}

        Runtime.getRuntime().exit(2)
    }
}
