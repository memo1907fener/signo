package at.signo.player

import android.content.Context
import android.provider.Settings

object Prefs {
    private const val FILE = "signo"
    private const val KEY_TOKEN = "player_token"

    fun token(ctx: Context): String? =
        ctx.getSharedPreferences(FILE, Context.MODE_PRIVATE).getString(KEY_TOKEN, null)

    fun setToken(ctx: Context, token: String) {
        ctx.getSharedPreferences(FILE, Context.MODE_PRIVATE)
            .edit().putString(KEY_TOKEN, token).apply()
    }

    fun clearToken(ctx: Context) {
        ctx.getSharedPreferences(FILE, Context.MODE_PRIVATE)
            .edit().remove(KEY_TOKEN).apply()
    }

    /** Stabile Geräte-Kennung – überlebt Neuinstallation der App nicht, aber App-Neustarts. */
    @Suppress("HardwareIds")
    fun deviceId(ctx: Context): String {
        val androidId = Settings.Secure.getString(ctx.contentResolver, Settings.Secure.ANDROID_ID)
        return if (androidId.isNullOrBlank()) "android-unknown" else "android-$androidId"
    }
}
