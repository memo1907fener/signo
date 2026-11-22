<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Schnittstelle für Bildschirme (Browser unter /tv und Android-App).
 * Grundsatz: Ein Bildschirm verliert seine Kopplung NIE durch Serverprobleme –
 * nur eine eindeutige JSON-Antwort {status: unknown} gilt als "gelöscht".
 */
class PlayerController extends Controller
{
    public function show()
    {
        return response()->view('player')->header('Cache-Control', 'no-cache');
    }

    public function register(Request $request): JsonResponse
    {
        $deviceId = substr(preg_replace('/[^A-Za-z0-9._:-]/', '', (string) $request->input('device_id')), 0, 120) ?: null;
        $client = str_starts_with((string) $deviceId, 'android-') ? 'android' : 'web';

        // Bekanntes Gerät? Dann denselben Bildschirm zurückgeben (keine doppelten Einträge)
        if ($deviceId) {
            $existing = Screen::where('device_id', $deviceId)->latest('id')->first();
            if ($existing) {
                if (! $existing->organization_id && ! $existing->pair_code) {
                    $existing->update(['pair_code' => $this->newCode()]);
                }
                return response()->json(['token' => $existing->token]);
            }
        }

        $key = 'player-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['status' => 'rate_limited'], 429);
        }
        RateLimiter::hit($key, 3600);

        $screen = Screen::create([
            'token' => Str::random(48),
            'pair_code' => $this->newCode(),
            'device_id' => $deviceId,
            'client' => $client,
            'resolution' => substr((string) $request->input('resolution'), 0, 20) ?: null,
            'ip' => $request->ip(),
        ]);

        return response()->json(['token' => $screen->token]);
    }

    public function content(Request $request, string $token): JsonResponse
    {
        $screen = Screen::with('organization')->where('token', $token)->first();
        if (! $screen) {
            return response()->json(['status' => 'unknown'], 404);
        }

        $this->touch($screen, $request);

        if (! $screen->organization_id) {
            if (! $screen->pair_code) $screen->update(['pair_code' => $this->newCode()]);
            return response()->json(['status' => 'pairing', 'code' => $screen->pair_code, 'poll' => 5]);
        }

        // Browser-Bildschirme holen "Neu laden" hier ab (Android über /agent)
        $commands = [];
        if ($screen->client !== 'android') {
            $pending = $screen->commands()->whereNull('acked_at')->where('command', 'reload')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get();
            foreach ($pending as $c) { $commands[] = $c->command; $c->update(['acked_at' => now()]); }
        }

        $org = $screen->organization;
        if (! $org->isActive()) {
            return response()->json([
                'status' => 'suspended',
                'message' => $org->locked_at ? 'Dieser Bildschirm ist derzeit gesperrt.' : 'Die Testphase ist abgelaufen. Bitte wählen Sie unter signo.at ein Paket.',
                'poll' => 60, 'commands' => $commands,
            ]);
        }

        if ($screen->isSleeping()) {
            return response()->json(['status' => 'sleeping', 'poll' => 30, 'commands' => $commands]);
        }

        $pages = ($screen->show_all ? $org->pages()->get() : $screen->pages()->where('organization_id', $org->id)->get())
            ->filter(fn (Page $p) => $p->isActiveAt())->values();

        // Jede Seite; bei "Sprachen abwechseln" zusätzlich jede Übersetzung als eigene Szene
        $out = collect();
        foreach ($pages as $p) {
            $variants = ['de'];
            if ($p->tv_languages) $variants = array_merge($variants, $p->availableLanguages());
            foreach ($variants as $lang) {
                $v = $p->inLanguage($lang);
                $out->push([
                    'id' => $p->id . ($lang === 'de' ? '' : '-' . $lang),
                    'revision' => $p->revision() . $lang,
                    'html' => view('display.page', ['page' => $v, 'mode' => 'tv'])->render(),
                    'media' => array_values(array_filter([
                        Page::fileUrl($p->data['image'] ?? null),
                        Page::fileUrl($p->data['file'] ?? null),
                    ])),
                ]);
            }
        }

        return response()->json([
            'status' => 'active',
            'revision' => substr(md5($screen->seconds . '|' . $out->pluck('revision')->implode(',')), 0, 12),
            'seconds' => $screen->seconds,
            'name' => $screen->name,
            'pages' => $out,
            'poll' => 30,
            'commands' => $commands,
        ]);
    }

    /** Android-App: Befehle abholen + Gerätezustand melden. */
    public function agent(Request $request, string $token): JsonResponse
    {
        $screen = Screen::where('token', $token)->first();
        if (! $screen) return response()->json(['status' => 'unknown'], 404);

        $screen->forceFill([
            'last_seen_at' => now(),
            'client' => 'android',
            'app_version' => $request->integer('v') ?: $screen->app_version,
        ])->save();

        $cmds = $screen->commands()->whereNull('acked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('id')->get(['id', 'command']);

        return response()->json(['commands' => $cmds]);
    }

    public function ack(Request $request, string $token): JsonResponse
    {
        $screen = Screen::where('token', $token)->first();
        if (! $screen) return response()->json(['status' => 'unknown'], 404);
        $ids = array_map('intval', (array) $request->input('ids', []));
        $screen->commands()->whereIn('id', $ids)->update(['acked_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function screenshot(Request $request, string $token): JsonResponse
    {
        $screen = Screen::where('token', $token)->first();
        if (! $screen || ! $screen->organization_id) return response()->json(['status' => 'unknown'], 404);
        $request->validate(['screenshot' => 'required|file|max:4096|mimes:jpg,jpeg,png']);

        $path = 'org/' . $screen->organization_id . '/screen-' . $screen->id . '.jpg';
        Storage::disk('public')->put($path, file_get_contents($request->file('screenshot')->getRealPath()));
        $screen->forceFill(['screenshot_path' => $path, 'screenshot_at' => now()])->save();
        $screen->commands()->whereNull('acked_at')->where('command', 'screenshot')->update(['acked_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function touch(Screen $screen, Request $request): void
    {
        $res = substr((string) $request->query('res', ''), 0, 20);
        $screen->forceFill([
            'last_seen_at' => now(),
            'ip' => $request->ip(),
            'resolution' => $res ?: $screen->resolution,
            'offline_notified_at' => null,
        ])->save();
    }

    private function newCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        } while (Screen::where('pair_code', $code)->exists());
        return $code;
    }
}
