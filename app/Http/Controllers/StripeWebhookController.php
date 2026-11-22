<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\Stripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('signo.stripe.webhook');
        $payload = $request->getContent();
        if (! $secret || ! Stripe::verifySignature($payload, $request->header('Stripe-Signature'), $secret)) {
            return response('Ungültige Signatur', 400);
        }

        $event = json_decode($payload, true);
        $obj = $event['data']['object'] ?? [];

        try {
            switch ($event['type'] ?? '') {
                case 'checkout.session.completed':
                    $org = Organization::find($obj['client_reference_id'] ?? 0)
                        ?? Organization::where('stripe_customer_id', $obj['customer'] ?? '')->first();
                    if ($org && ! empty($obj['subscription'])) {
                        Stripe::syncSubscription($org, Stripe::get('subscriptions/' . $obj['subscription']));
                    }
                    break;

                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $org = Organization::where('stripe_subscription_id', $obj['id'] ?? '')->first()
                        ?? Organization::find($obj['metadata']['org_id'] ?? 0)
                        ?? Organization::where('stripe_customer_id', $obj['customer'] ?? '')->first();
                    if ($org) Stripe::syncSubscription($org, $obj);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('Stripe-Webhook', ['type' => $event['type'] ?? null, 'error' => $e->getMessage()]);
            return response('Fehler', 500);
        }

        return response('ok');
    }
}
