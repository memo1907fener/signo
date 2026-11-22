<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Schlanke Stripe-Anbindung über die REST-Schnittstelle (ohne zusätzliche Bibliothek). */
class Stripe
{
    public static function enabled(): bool
    {
        return filled(config('signo.stripe.secret'));
    }

    private static function api()
    {
        return Http::withBasicAuth(config('signo.stripe.secret'), '')->asForm()->timeout(30)
            ->withHeaders(['Stripe-Version' => '2024-06-20']);
    }

    public static function get(string $path, array $q = []): array
    {
        $r = self::api()->get('https://api.stripe.com/v1/' . $path, $q);
        if (! $r->successful()) throw new RuntimeException('Stripe: ' . ($r->json('error.message') ?? $r->status()));
        return $r->json();
    }

    public static function post(string $path, array $data = []): array
    {
        $r = self::api()->post('https://api.stripe.com/v1/' . $path, $data);
        if (! $r->successful()) throw new RuntimeException('Stripe: ' . ($r->json('error.message') ?? $r->status()));
        return $r->json();
    }

    public static function delete(string $path): void
    {
        self::api()->delete('https://api.stripe.com/v1/' . $path);
    }

    public static function customer(Organization $org, string $email): string
    {
        if ($org->stripe_customer_id) return $org->stripe_customer_id;
        $c = self::post('customers', [
            'name' => $org->name,
            'email' => $email,
            'preferred_locales' => ['de'],
            'metadata' => ['org_id' => $org->id],
        ]);
        $org->update(['stripe_customer_id' => $c['id']]);
        return $c['id'];
    }

    public static function checkoutUrl(Organization $org, string $plan, string $email): string
    {
        $price = config("signo.plans.$plan.stripe_price");
        if (! $price) throw new RuntimeException('Für dieses Paket ist noch kein Preis hinterlegt.');

        $s = self::post('checkout/sessions', [
            'mode' => 'subscription',
            'customer' => self::customer($org, $email),
            'client_reference_id' => $org->id,
            'line_items' => [['price' => $price, 'quantity' => 1]],
            'success_url' => route('account') . '?bezahlt=1',
            'cancel_url' => route('account'),
            'locale' => 'de',
            'allow_promotion_codes' => 'true',
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => 'true'],
            'customer_update' => ['address' => 'auto', 'name' => 'auto'],
            'subscription_data' => ['metadata' => ['org_id' => $org->id, 'plan' => $plan]],
        ]);
        return $s['url'];
    }

    public static function portalUrl(Organization $org): string
    {
        if (! $org->stripe_customer_id) throw new RuntimeException('Noch kein Kundenkonto vorhanden.');
        $s = self::post('billing_portal/sessions', ['customer' => $org->stripe_customer_id, 'return_url' => route('account')]);
        return $s['url'];
    }

    /** Abo-Daten von Stripe in die Organisation übernehmen. */
    public static function syncSubscription(Organization $org, array $sub): void
    {
        $priceId = $sub['items']['data'][0]['price']['id'] ?? null;
        $plan = collect(config('signo.plans'))->search(fn ($p) => $p['stripe_price'] && $p['stripe_price'] === $priceId)
            ?: ($sub['metadata']['plan'] ?? $org->plan);
        $periodEnd = $sub['current_period_end'] ?? ($sub['items']['data'][0]['current_period_end'] ?? null);
        $status = $sub['status'] ?? 'canceled';

        $endsAt = null;
        if ($status === 'canceled') $endsAt = now();
        elseif (! empty($sub['cancel_at_period_end']) && $periodEnd) $endsAt = \Illuminate\Support\Carbon::createFromTimestamp($periodEnd);

        $org->update([
            'stripe_subscription_id' => $sub['id'],
            'stripe_customer_id' => $sub['customer'] ?? $org->stripe_customer_id,
            'subscription_status' => $status,
            'subscription_ends_at' => $endsAt,
            'plan' => $plan,
        ]);
    }

    public static function verifySignature(string $payload, ?string $header, string $secret): bool
    {
        if (! $header) return false;
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, null);
            $parts[$k][] = $v;
        }
        $t = (int) ($parts['t'][0] ?? 0);
        if (! $t || abs(time() - $t) > 300) return false;
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        foreach ($parts['v1'] ?? [] as $sig) {
            if (hash_equals($expected, (string) $sig)) return true;
        }
        return false;
    }
}
