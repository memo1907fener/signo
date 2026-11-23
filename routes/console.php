<?php

use App\Models\Organization;
use App\Models\Screen;
use App\Models\ScreenCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Aufräumen: nie gekoppelte Bildschirme und alte Befehle
Artisan::command('signo:cleanup', function () {
    $n = Screen::whereNull('organization_id')
        ->where('created_at', '<', now()->subDays(3))
        ->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDay()))
        ->delete();
    $c = ScreenCommand::where('created_at', '<', now()->subDays(7))->delete();
    $o = \App\Models\Order::where('created_at', '<', now()->subDays(90))->delete();
    \Illuminate\Support\Facades\DB::table('page_views')->where('day', '<', now()->subDays(400)->format('Y-m-d'))->delete();
    \Illuminate\Support\Facades\DB::table('screen_days')->where('day', '<', now()->subDays(400)->format('Y-m-d'))->delete();
    \App\Models\StampCard::where('updated_at', '<', now()->subDays(540))->delete();
    $this->info("$n ungekoppelte Bildschirme, $c alte Befehle und $o alte Bestellungen entfernt.");
})->purpose('Alte, nie gekoppelte Bildschirme entfernen');

// Erinnerung: 3 Tage vor Ende der Testphase und am Tag danach
Artisan::command('signo:trial-reminders', function () {
    $sent = 0;
    $orgs = Organization::with('users')->whereNull('stripe_subscription_id')->whereNull('locked_at')
        ->whereNotNull('trial_ends_at')->where('reminder_stage', '<', 2)->get();

    foreach ($orgs as $org) {
        $user = $org->owners()->first() ?? $org->users->first();
        if (! $user) continue;
        $stage = null;
        if ($org->reminder_stage < 1 && $org->trial_ends_at->isFuture() && $org->trial_ends_at->lte(now()->addDays(3))) $stage = 1;
        elseif ($org->trial_ends_at->isPast()) $stage = 2;
        if (! $stage) continue;

        $subject = $stage === 1 ? 'Ihre Signo-Testphase endet bald' : 'Ihre Signo-Testphase ist abgelaufen';
        $text = $stage === 1
            ? "Hallo {$user->name},\n\nIhre kostenlose Testphase endet am {$org->trial_ends_at->format('d.m.Y')}. Damit Ihre Bildschirme und QR-Seiten ohne Unterbrechung weiterlaufen, wählen Sie einfach ein Paket:\n\n" . route('account') . "\n\nWenn Sie nichts tun, endet die Testphase automatisch – es wird nichts abgebucht.\n\nViele Grüße\nIhr Signo-Team"
            : "Hallo {$user->name},\n\nIhre Testphase ist abgelaufen, Ihre Bildschirme und QR-Seiten sind pausiert. Ihre Inhalte bleiben gespeichert. Mit einem Paket geht es sofort weiter:\n\n" . route('account') . "\n\nViele Grüße\nIhr Signo-Team";
        try {
            Mail::raw($text, fn ($m) => $m->to($user->email)->subject($subject));
            $org->update(['reminder_stage' => $stage]);
            $sent++;
        } catch (\Throwable $e) {
            report($e);
        }
    }
    $this->info("$sent Erinnerungen versendet.");
})->purpose('Erinnerungs-Mails zur Testphase');

// Ausfall-Alarm für Bildschirme
Artisan::command('signo:offline-alerts', function () {
    $this->info(\App\Support\Alerts::checkOffline() . ' Ausfälle gemeldet.');
})->purpose('E-Mail, wenn ein Bildschirm während der Betriebszeit offline ist');

Schedule::command('signo:offline-alerts')->everyFiveMinutes();
Schedule::command('signo:cleanup')->dailyAt('04:10');
Schedule::command('signo:trial-reminders')->hourly();
