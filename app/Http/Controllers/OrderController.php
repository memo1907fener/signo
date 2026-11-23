<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /** Gast schickt Bestellung von der Handy-Seite. */
    public function store(Request $request, string $slug)
    {
        $page = Page::with('organization')->where('slug', $slug)->firstOrFail();
        $org = $page->organization;
        if (! $page->is_public || ! $page->orderable || ! $org->ordering_enabled || ! $org->isActive()) {
            return response()->json(['message' => 'Bestellen ist hier gerade nicht möglich.'], 422);
        }
        if ($request->filled('website')) {
            return response()->json(['message' => 'Bestellung nicht möglich.'], 422);
        }
        $key = 'order:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json(['message' => 'Zu viele Bestellungen. Bitte kurz warten oder beim Personal bestellen.'], 429);
        }

        $v = $request->validate([
            'items' => 'required|array|min:1|max:40',
            'items.*.s' => 'required|integer|min:0',
            'items.*.i' => 'required|integer|min:0',
            'items.*.q' => 'required|integer|min:1|max:20',
            'name' => 'nullable|string|max:60',
            'table' => 'nullable|string|max:20',
            'note' => 'nullable|string|max:300',
            'lang' => 'nullable|string|max:5',
        ]);

        if ($org->ordering_mode === 'table' && blank($v['table'] ?? null)) {
            return response()->json(['message' => 'Bitte die Tischnummer angeben.'], 422);
        }
        if ($org->ordering_mode === 'pickup' && blank($v['name'] ?? null)) {
            return response()->json(['message' => 'Bitte einen Namen für die Abholung angeben.'], 422);
        }

        // Preise und Namen IMMER aus den gespeicherten Daten – nie vom Handy übernehmen
        $items = []; $total = 0;
        foreach ($v['items'] as $row) {
            $it = $page->data['sections'][$row['s']]['items'][$row['i']] ?? null;
            // Aktionspreis gilt nur innerhalb der Happy Hour – der Server entscheidet
            $price = $it ? Order::parsePrice($page->priceOf($it)[0]) : null;
            if (! $it || ! $price) {
                return response()->json(['message' => 'Die Karte wurde gerade geändert. Bitte Seite neu laden.'], 409);
            }
            if (Page::soldOut($it)) {
                return response()->json(['message' => '„' . $it['name'] . '“ ist heute leider ausverkauft. Bitte aus dem Warenkorb entfernen.'], 409);
            }
            $items[] = ['name' => $it['name'], 'qty' => (int) $row['q'], 'price' => $price];
            $total += $price * (int) $row['q'];
        }

        RateLimiter::hit($key, 600);

        $order = DB::transaction(function () use ($org, $page, $items, $total, $v, $request) {
            $number = (int) Order::where('organization_id', $org->id)->whereDate('created_at', today())->lockForUpdate()->max('number') + 1;
            return Order::create([
                'organization_id' => $org->id,
                'page_id' => $page->id,
                'number' => $number,
                'code' => Str::lower(Str::random(12)),
                'status' => 'new',
                'customer_name' => trim((string) ($v['name'] ?? '')) ?: null,
                'table_no' => trim((string) ($v['table'] ?? '')) ?: null,
                'note' => trim((string) ($v['note'] ?? '')) ?: null,
                'items' => $items,
                'total_cents' => $total,
                'lang' => $v['lang'] ?? null,
                'ip' => $request->ip(),
            ]);
        });

        return response()->json(['url' => route('order.status', $order->code)]);
    }

    /** Statusseite für den Gast. */
    public function status(string $code)
    {
        $order = Order::with('organization')->where('code', $code)->firstOrFail();
        return view('orders.status', ['order' => $order, 'org' => $order->organization]);
    }

    public function statusJson(string $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        return response()->json(['status' => $order->status, 'number' => $order->number])->header('Cache-Control', 'no-store');
    }

    /** Abholanzeige für einen Bildschirm (ohne Anmeldung, über geheimen Link). */
    public function board(Request $request, string $token)
    {
        $org = Organization::where('kitchen_token', $token)->firstOrFail();
        return view('orders.board', ['org' => $org, 'token' => $token, 'theme' => $request->query('theme') === 'light' ? 'light' : 'dark']);
    }

    public function boardJson(string $token)
    {
        $org = Organization::where('kitchen_token', $token)->firstOrFail();
        $orders = Order::where('organization_id', $org->id)
            ->where('created_at', '>', now()->subHours(12))
            ->where(fn ($q) => $q->whereIn('status', ['new', 'preparing'])
                ->orWhere(fn ($q) => $q->where('status', 'ready')->where('ready_at', '>', now()->subMinutes(30))))
            ->orderBy('number')->get(['number', 'status']);

        return response()->json([
            'preparing' => $orders->whereIn('status', ['new', 'preparing'])->pluck('number')->values(),
            'ready' => $orders->where('status', 'ready')->pluck('number')->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
