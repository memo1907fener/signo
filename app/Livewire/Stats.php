<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithOrg;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Statistik: QR-Aufrufe, Bestellungen, beliebteste Gerichte, Laufzeit der Bildschirme. */
#[Layout('layouts.app')]
class Stats extends Component
{
    use WithOrg;

    #[Url(as: 'tage')]
    public int $days = 30;

    public function render()
    {
        if (! in_array($this->days, [7, 30, 90], true)) $this->days = 30;
        $org = $this->org();
        $tz = config('app.timezone');
        $today = now()->timezone($tz)->startOfDay();
        $from = $today->copy()->subDays($this->days - 1);
        $dayKeys = collect(range(0, $this->days - 1))->map(fn ($i) => $from->copy()->addDays($i)->format('Y-m-d'));

        // Aufrufe der Handy-Seiten
        $views = DB::table('page_views')->where('organization_id', $org->id)->where('day', '>=', $from->format('Y-m-d'))
            ->selectRaw('day, sum(views) as n')->groupBy('day')->pluck('n', 'day')
            ->mapWithKeys(fn ($n, $day) => [substr((string) $day, 0, 10) => (int) $n]);
        $topPages = DB::table('page_views')->join('pages', 'pages.id', '=', 'page_views.page_id')
            ->where('page_views.organization_id', $org->id)->where('day', '>=', $from->format('Y-m-d'))
            ->selectRaw('pages.title, sum(views) as n')->groupBy('pages.id', 'pages.title')->orderByDesc('n')->limit(6)->get();

        // Bestellungen (Uhrzeiten in Ortszeit)
        $orders = $org->orders()->where('created_at', '>=', $from->copy()->utc())->where('status', '!=', 'canceled')
            ->get(['items', 'total_cents', 'created_at']);
        $perDay = []; $revenue = []; $hours = array_fill(0, 24, 0); $items = [];
        foreach ($orders as $o) {
            $at = $o->created_at->copy()->timezone($tz);
            $k = $at->format('Y-m-d');
            $perDay[$k] = ($perDay[$k] ?? 0) + 1;
            $revenue[$k] = ($revenue[$k] ?? 0) + $o->total_cents;
            $hours[(int) $at->format('G')]++;
            foreach (($o->items ?? []) as $it) {
                $name = (string) ($it['name'] ?? '');
                if ($name === '') continue;
                $items[$name]['qty'] = ($items[$name]['qty'] ?? 0) + (int) ($it['qty'] ?? 1);
                $items[$name]['sum'] = ($items[$name]['sum'] ?? 0) + (int) ($it['price'] ?? 0) * (int) ($it['qty'] ?? 1);
            }
        }
        uasort($items, fn ($a, $b) => $b['qty'] <=> $a['qty']);

        // Laufzeit der Bildschirme
        $screens = $org->screens()->get(['id', 'name', 'paired_at', 'last_seen_at']);
        $uptime = DB::table('screen_days')->whereIn('screen_id', $screens->pluck('id'))->where('day', '>=', $from->format('Y-m-d'))
            ->selectRaw('screen_id, sum(seconds) as s, count(*) as d')->groupBy('screen_id')->get()->keyBy('screen_id');

        $fb = $org->feedbacks()->where('created_at', '>=', $from->copy()->utc());

        return view('livewire.stats', [
            'org' => $org,
            'labels' => $dayKeys->map(fn ($d) => Carbon::parse($d)->format('d.m.'))->all(),
            'viewSeries' => $dayKeys->map(fn ($d) => (int) ($views[$d] ?? 0))->all(),
            'orderSeries' => $dayKeys->map(fn ($d) => (int) ($perDay[$d] ?? 0))->all(),
            'revenueSeries' => $dayKeys->map(fn ($d) => (int) ($revenue[$d] ?? 0))->all(),
            'viewsTotal' => (int) $views->sum(),
            'ordersTotal' => $orders->count(),
            'revenueTotal' => (int) $orders->sum('total_cents'),
            'topPages' => $topPages,
            'topItems' => array_slice($items, 0, 8, true),
            'hours' => $hours,
            'screens' => $screens,
            'uptime' => $uptime,
            'fbCount' => (clone $fb)->count(),
            'fbAvg' => round((float) (clone $fb)->avg('rating'), 1),
        ])->title(__('Statistik'));
    }
}
