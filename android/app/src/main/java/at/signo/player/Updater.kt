package at.signo.player

import android.content.Context
import android.content.pm.PackageInstaller
import java.io.InputStream
import java.net.HttpURLConnection
import java.net.URL

/**
 * Lädt eine neue APK vom Server und installiert sie still.
 * Funktioniert nur als Device Owner – sonst wird nichts installiert.
 */
object Updater {

    fun updateFromServer(ctx: Context): Boolean {
        if (!Lockdown.isOwner(ctx)) return false

        return try {
            // Token mitschicken, damit der Server den richtigen Kanal ausliefert
            val token = Prefs.token(ctx) ?: ""
            val suffix = if (token.isNotBlank()) "?token=$token" else ""
            val url = URL("${BuildConfig.BASE_URL}/app/signo.apk$suffix")
            val conn = url.openConnection() as HttpURLConnection
            conn.connectTimeout = 20000
            conn.readTimeout = 120000
            if (conn.responseCode !in 200..299) return false

            val length = conn.contentLengthLong
            conn.inputStream.use { input -> install(ctx, input, length) }
        } catch (e: Exception) { false }
    }

    private fun install(ctx: Context, input: InputStream, length: Long): Boolean = try {
        val installer = ctx.packageManager.packageInstaller
        val params = PackageInstaller.SessionParams(
            PackageInstaller.SessionParams.MODE_FULL_INSTALL
        )
        val sessionId = installer.createSession(params)
        val session = installer.openSession(sessionId)

        session.openWrite("apk", 0, length).use { out ->
            val buf = ByteArray(65536)
            while (true) {
                val n = input.read(buf)
                if (n <= 0) break
                out.write(buf, 0, n)
            }
            session.fsync(out)
        }

        val intent = android.content.Intent(ctx, MainActivity::class.java)
        val flags = if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.M)
            android.app.PendingIntent.FLAG_UPDATE_CURRENT or android.app.PendingIntent.FLAG_IMMUTABLE
        else android.app.PendingIntent.FLAG_UPDATE_CURRENT

        val pi = android.app.PendingIntent.getActivity(ctx, 0, intent, flags)
        session.commit(pi.intentSender)
        session.close()
        true
    } catch (e: Exception) { false }
}
