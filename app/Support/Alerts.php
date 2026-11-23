<?php

namespace App\Support;

use App\Models\Screen;
use Illuminate\Support\Facades\Mail;

/** Ausfall-Alarm: E-Mail, wenn ein Bildschirm während der Betriebszeit länger nichts von sich hören lässt. */
class Alerts
{
    /** Alle fälligen Ausfälle melden; gibt die Anzahl der Meldungen zurück. */
    public static function checkOffline(): int
    {
        $minutes = max(5, (int) config('signo.offline_minutes'));
        $sent = 0;
        $screens = Screen::with('organization')
            ->whereNotNull('organization_id')->whereNull('offline_notified_at')
            ->where('last_seen_at', '<', now()->subMinutes($minutes))
            ->where('last_seen_at', '>', now()->subDays(2))
            ->get();

        foreach ($screens as $screen) {
            $org = $screen->organization;
            if (! $org || ! $org->offline_alerts || ! $org->isActive()) continue;
            // Nur während der Betriebszeit – und erst, wenn sie schon eine Weile läuft (Fernseher wird morgens eingeschaltet)
            if ($screen->isSleeping() || $screen->isSleeping(now()->subMinutes($minutes))) continue;

            $since = $screen->last_seen_at->timezone(config('app.timezone'))->format('H:i');
            self::mail($screen, fn ($l) => __('Bildschirm „:name“ ist offline', ['name' => $screen->name], $l),
                fn ($l, $user) => __("Hallo :user,\n\nIhr Bildschirm „:name“ (:org) meldet sich seit :time Uhr nicht mehr.\n\nMeist hilft: Fernseher und TV-Box einschalten und die Internetverbindung prüfen. Sobald er wieder läuft, bekommen Sie eine kurze Entwarnung.\n\nBildschirme ansehen: :url\n\nSchalten Sie den Fernseher abends aus? Dann tragen Sie beim Bildschirm Betriebszeiten ein – außerhalb davon gibt es keinen Alarm.\n\nViele Grüße\nIhr Signo-Team",
                    ['user' => $user->name, 'name' => $screen->name, 'org' => $org->name, 'time' => $since, 'url' => route('screens.index')], $l));
            $screen->forceFill(['offline_notified_at' => now()])->save();
            $sent++;
        }
        return $sent;
    }

    /** Entwarnung, sobald sich ein gemeldeter Bildschirm wieder meldet. */
    public static function backOnline(Screen $screen): void
    {
        $org = $screen->organization;
        if (! $org || ! $org->offline_alerts) return;
        self::mail($screen, fn ($l) => __('Bildschirm „:name“ läuft wieder', ['name' => $screen->name], $l),
            fn ($l, $user) => __("Hallo :user,\n\ngute Nachricht: Ihr Bildschirm „:name“ (:org) ist wieder online und zeigt Ihre Inhalte.\n\nViele Grüße\nIhr Signo-Team",
                ['user' => $user->name, 'name' => $screen->name, 'org' => $org->name], $l));
    }

    private static function mail(Screen $screen, \Closure $subject, \Closure $body): void
    {
        foreach ($screen->organization->owners()->get() as $user) {
            $l = $user->locale ?: 'de';
            try {
                Mail::raw($body($l, $user), fn ($m) => $m->to($user->email)->subject($subject($l)));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
