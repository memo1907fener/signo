<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Organization;
use App\Models\StampCard;
use App\Support\GuestTexts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** Gäste-Seiten ohne Anmeldung: Feedback und digitale Stempelkarte. */
class GuestController extends Controller
{
    private function org(string $token): Organization
    {
        $org = Organization::where('public_token', $token)->firstOrFail();
        abort_unless($org->isActive(), 404);
        return $org;
    }

    // ---------- Feedback ----------
    public function feedback(Request $request, string $token)
    {
        $org = $this->org($token);
        abort_unless($org->feedback_enabled, 404);
        $lang = GuestTexts::pick($request);
        return view('guest.feedback', ['org' => $org, 'token' => $token, 'lang' => $lang, 't' => GuestTexts::for($lang)]);
    }

    public function feedbackStore(Request $request, string $token)
    {
        $org = $this->org($token);
        abort_unless($org->feedback_enabled, 404);
        if ($request->filled('website')) return response()->json(['ok' => true]);

        $key = 'feedback:' . $org->id . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) return response()->json(['message' => 'Zu viele Rückmeldungen. Bitte später erneut.'], 429);

        $v = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'contact' => 'nullable|string|max:120',
            'lang' => 'nullable|string|max:5',
        ]);
        RateLimiter::hit($key, 3600);

        Feedback::create([
            'organization_id' => $org->id,
            'rating' => (int) $v['rating'],
            'comment' => trim((string) ($v['comment'] ?? '')) ?: null,
            'contact' => trim((string) ($v['contact'] ?? '')) ?: null,
            'lang' => $v['lang'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }

    // ---------- Stempelkarte ----------
    /** QR-Code am Tresen: vorhandene Karte öffnen oder eine neue anlegen. */
    public function stampEntry(Request $request, string $token)
    {
        $org = $this->org($token);
        if (! $org->loyaltyOn()) {
            return response()->view('guest.card-off', ['org' => $org, 't' => GuestTexts::for(GuestTexts::pick($request))], 404);
        }

        $cookie = 'signo_card_' . $org->id;
        $code = (string) $request->cookie($cookie);
        $card = $code !== '' ? StampCard::where('organization_id', $org->id)->where('code', $code)->first() : null;

        if (! $card) {
            // Keine Karten für Suchmaschinen und Link-Vorschauen anlegen
            if (preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|slurp/i', (string) $request->userAgent())) abort(404);
            $key = 'card-new:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) abort(429);
            RateLimiter::hit($key, 3600);
            do { $code = Str::lower(Str::random(16)); } while (StampCard::where('code', $code)->exists());
            $card = StampCard::create(['organization_id' => $org->id, 'code' => $code]);
        }

        return redirect()->route('card', $card->code)->cookie($cookie, $card->code, 60 * 24 * 730);
    }

    public function card(Request $request, string $code)
    {
        $card = StampCard::with('organization')->where('code', $code)->firstOrFail();
        $org = $card->organization;
        $lang = GuestTexts::pick($request);
        if (! $org->isActive() || ! $org->loyaltyOn()) {
            return response()->view('guest.card-off', ['org' => $org, 't' => GuestTexts::for($lang)], 404);
        }
        // Wer den Link seiner Karte öffnet (z. B. vom Startbildschirm), hat sie auf diesem Gerät wieder
        return response()->view('guest.card', [
            'org' => $org, 'card' => $card, 'lang' => $lang, 't' => GuestTexts::for($lang), 'goal' => $org->loyaltyGoal(),
        ])->cookie('signo_card_' . $org->id, $card->code, 60 * 24 * 730)->header('Cache-Control', 'no-store');
    }

    /** Personal bestätigt mit seinem Code: Stempel geben. */
    public function stamp(Request $request, string $code)
    {
        return $this->withPin($request, $code, function (StampCard $card, Organization $org, array $t) use ($request) {
            $count = max(1, min(5, (int) $request->input('count', 1)));
            $goal = $org->loyaltyGoal();

            // Schutz vor Missbrauch: höchstens 10 Stempel je Karte in 24 Stunden
            $dayKey = 'card-day:' . $card->id;
            $given = (int) RateLimiter::attempts($dayKey);
            if ($given + $count > 10) return response()->json(['message' => $t['st_limit']], 429);
            for ($i = 0; $i < $count; $i++) RateLimiter::hit($dayKey, 86400);

            $card->update([
                'stamps' => min($goal * 3, $card->stamps + $count),
                'total_stamps' => $card->total_stamps + $count,
                'last_stamp_at' => now(),
            ]);
            return response()->json(['stamps' => $card->stamps, 'goal' => $goal]);
        });
    }

    /** Personal bestätigt mit seinem Code: volle Karte einlösen. */
    public function redeem(Request $request, string $code)
    {
        return $this->withPin($request, $code, function (StampCard $card, Organization $org, array $t) {
            $goal = $org->loyaltyGoal();
            if ($card->stamps < $goal) return response()->json(['message' => $t['error']], 422);
            $card->update(['stamps' => $card->stamps - $goal, 'redeemed' => $card->redeemed + 1]);
            return response()->json(['stamps' => $card->stamps, 'goal' => $goal, 'redeemed' => true]);
        });
    }

    private function withPin(Request $request, string $code, \Closure $then)
    {
        $t = GuestTexts::for((string) $request->input('lang'));
        return DB::transaction(function () use ($request, $code, $then, $t) {
            $card = StampCard::where('code', $code)->lockForUpdate()->firstOrFail();
            $org = $card->organization;
            if (! $org->isActive() || ! $org->loyaltyOn()) return response()->json(['message' => $t['st_off']], 404);

            // Falscher Code: nach 5 Versuchen 15 Minuten Pause (je Karte und je Anschluss)
            $keys = ['card-pin:' . $card->id, 'card-pin-ip:' . $request->ip()];
            foreach ($keys as $k) if (RateLimiter::tooManyAttempts($k, 5)) return response()->json(['message' => $t['st_locked']], 429);
            if (! hash_equals((string) $org->loyalty['pin'], (string) $request->input('pin'))) {
                foreach ($keys as $k) RateLimiter::hit($k, 900);
                return response()->json(['message' => $t['st_wrong']], 422);
            }
            foreach ($keys as $k) RateLimiter::clear($k);

            return $then($card, $org, $t);
        });
    }
}
