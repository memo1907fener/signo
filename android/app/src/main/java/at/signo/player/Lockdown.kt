package at.signo.player

import android.app.admin.DevicePolicyManager
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.media.AudioManager
import android.os.Build
import android.os.UserManager
import android.provider.Settings

/**
 * Alles, was nur als Device Owner möglich ist.
 * Ohne Device Owner tun die Methoden nichts und melden false.
 */
object Lockdown {

    fun dpm(ctx: Context): DevicePolicyManager =
        ctx.getSystemService(Context.DEVICE_POLICY_SERVICE) as DevicePolicyManager

    fun admin(ctx: Context): ComponentName =
        ComponentName(ctx, AdminReceiver::class.java)

    fun isOwner(ctx: Context): Boolean =
        try { dpm(ctx).isDeviceOwnerApp(ctx.packageName) } catch (e: Exception) { false }

    /** Grundsperrung beim Start anwenden. */
    fun apply(ctx: Context) {
        if (!isOwner(ctx)) return
        val dp = dpm(ctx)
        val cn = admin(ctx)

        try { dp.setLockTaskPackages(cn, arrayOf(ctx.packageName)) } catch (e: Exception) {}

        try {
            val filter = IntentFilter(Intent.ACTION_MAIN).apply {
                addCategory(Intent.CATEGORY_HOME)
                addCategory(Intent.CATEGORY_DEFAULT)
            }
            dp.addPersistentPreferredActivity(cn, filter, ComponentName(ctx, MainActivity::class.java))
        } catch (e: Exception) {}

        for (r in listOf(
            UserManager.DISALLOW_FACTORY_RESET,
            UserManager.DISALLOW_SAFE_BOOT,
            UserManager.DISALLOW_ADD_USER,
            UserManager.DISALLOW_UNINSTALL_APPS,
            UserManager.DISALLOW_INSTALL_UNKNOWN_SOURCES
        )) {
            try { dp.addUserRestriction(cn, r) } catch (e: Exception) {}
        }

        try { dp.setGlobalSetting(cn, Settings.Global.STAY_ON_WHILE_PLUGGED_IN, "7") } catch (e: Exception) {}
        try { dp.setGlobalSetting(cn, Settings.Global.DEVICE_PROVISIONED, "1") } catch (e: Exception) {}

        // Automatische Zeit über Netzwerk
        try { dp.setGlobalSetting(cn, Settings.Global.AUTO_TIME, "1") } catch (e: Exception) {}
        try { dp.setGlobalSetting(cn, Settings.Global.AUTO_TIME_ZONE, "1") } catch (e: Exception) {}
    }

    /** Gerät neu starten. */
    fun reboot(ctx: Context): Boolean {
        if (!isOwner(ctx)) return false
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) { dpm(ctx).reboot(admin(ctx)); true } else false
        } catch (e: Exception) { false }
    }

    /** Bildschirm sperren (Display aus). */
    fun screenOff(ctx: Context): Boolean = try { dpm(ctx).lockNow(); true } catch (e: Exception) { false }

    /** Helligkeit 0–100. */
    fun brightness(ctx: Context, percent: Int): Boolean {
        if (!isOwner(ctx)) return false
        val v = (percent.coerceIn(0, 100) * 255 / 100)
        return try {
            val dp = dpm(ctx); val cn = admin(ctx)
            dp.setSystemSetting(cn, Settings.System.SCREEN_BRIGHTNESS_MODE, "0")
            dp.setSystemSetting(cn, Settings.System.SCREEN_BRIGHTNESS, v.toString())
            true
        } catch (e: Exception) { false }
    }

    /** Medienlautstärke 0–100. */
    fun volume(ctx: Context, percent: Int): Boolean = try {
        val am = ctx.getSystemService(Context.AUDIO_SERVICE) as AudioManager
        val max = am.getStreamMaxVolume(AudioManager.STREAM_MUSIC)
        am.setStreamVolume(AudioManager.STREAM_MUSIC, percent.coerceIn(0, 100) * max / 100, 0)
        true
    } catch (e: Exception) { false }

    /** Gerät zurücksetzen (Fernlöschung). */
    fun wipe(ctx: Context): Boolean {
        if (!isOwner(ctx)) return false
        return try {
            dpm(ctx).clearUserRestriction(admin(ctx), UserManager.DISALLOW_FACTORY_RESET)
            dpm(ctx).wipeData(0)
            true
        } catch (e: Exception) { false }
    }

    /** Sperrung aufheben: Geraet wird wieder frei (fuer Wartung/Umzug). */
    fun releaseOwner(ctx: Context): Boolean {
        if (!isOwner(ctx)) return false
        return try {
            val dp = dpm(ctx)
            val cn = admin(ctx)
            for (r in listOf(
                UserManager.DISALLOW_FACTORY_RESET,
                UserManager.DISALLOW_SAFE_BOOT,
                UserManager.DISALLOW_ADD_USER,
                UserManager.DISALLOW_UNINSTALL_APPS,
                UserManager.DISALLOW_INSTALL_UNKNOWN_SOURCES
            )) {
                try { dp.clearUserRestriction(cn, r) } catch (e: Exception) {}
            }
            try { dp.clearPackagePersistentPreferredActivities(cn, ctx.packageName) } catch (e: Exception) {}
            dp.clearDeviceOwnerApp(ctx.packageName)
            true
        } catch (e: Exception) { false }
    }
}
