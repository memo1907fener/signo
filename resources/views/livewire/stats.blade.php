@php
    // Einfache Balkenreihe: ein Balken je Tag, Wert beim Darüberfahren
    $bars = function (array $series, array $labels, ?\Closure $fmt = null) {
        $max = max(1, max($series));
        $fmt ??= fn ($v) => $v;
        $out = '<div class="flex h-36 items-end gap-[3px]">';
        foreach ($series as $i => $v) {
            $h = $v > 0 ? max(4, round($v / $max * 100)) : 0;
            $out .= '<div class="group relative flex h-full flex-1 items-end"><div class="w-full rounded-t-[4px] bg-ink/85 transition group-hover:bg-brand" style="height:' . $h . '%"></div>'
                . ($v === 0 ? '<div class="absolute inset-x-0 bottom-0 h-px bg-black/10"></div>' : '')
                . '<div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-ink px-2 py-1 text-xs text-white group-hover:block">' . e($labels[$i]) . ' · ' . e($fmt($v)) . '</div></div>';
        }
        return $out . '</div><div class="mt-1.5 flex justify-between text-xs text-muted"><span>' . e($labels[0]) . '</span><span>' . e(end($labels)) . '</span></div>';
    };
    $euro = fn ($c) => \App\Models\Order::euro((int) $c);
@endphp
<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ __('Statistik') }}</h1>
            <p class="mt-1 text-muted">{{ __('Was Ihre Gäste ansehen und bestellen – und wie zuverlässig Ihre Bildschirme laufen.') }}</p>
        </div>
        <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
            @foreach ([7, 30, 90] as $d)
                <button wire:click="$set('days', {{ $d }})" class="rounded-full px-4 py-1.5 {{ $days === $d ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __(':n Tage', ['n' => $d]) }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5"><p class="text-sm text-muted">{{ __('Aufrufe per QR-Code') }}</p><p class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($viewsTotal, 0, ',', '.') }}</p></div>
        <div class="card p-5"><p class="text-sm text-muted">{{ __('Bestellungen') }}</p><p class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($ordersTotal, 0, ',', '.') }}</p></div>
        <div class="card p-5"><p class="text-sm text-muted">{{ __('Umsatz über Bestellungen') }}</p><p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $euro($revenueTotal) }}</p>
            @if ($ordersTotal)<p class="text-xs text-muted">{{ __('Ø :sum je Bestellung', ['sum' => $euro($revenueTotal / $ordersTotal)]) }}</p>@endif</div>
        <div class="card p-5"><p class="text-sm text-muted">{{ __('Bewertung der Gäste') }}</p>
            @if ($fbCount)<p class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($fbAvg, 1, ',', '.') }} <span class="text-xl text-amber-500">★</span></p><p class="text-xs text-muted">{{ trans_choice(':n Rückmeldung|:n Rückmeldungen', $fbCount, ['n' => $fbCount]) }}</p>
            @else<p class="mt-1 text-3xl font-extrabold text-black/20">–</p><p class="text-xs text-muted"><a href="{{ route('guests') }}" wire:navigate class="underline">{{ __('Feedback einschalten') }}</a></p>@endif</div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-6">
            <h2 class="font-semibold">{{ __('Aufrufe der Handy-Seiten je Tag') }}</h2>
            <div class="mt-5">{!! $bars($viewSeries, $labels) !!}</div>
            @if ($topPages->count())
                <div class="mt-5 space-y-1.5 border-t border-black/5 pt-4 text-sm">
                    @foreach ($topPages as $p)
                        <div class="flex justify-between gap-3"><span class="truncate">{{ $p->title }}</span><span class="tabular-nums text-muted">{{ number_format($p->n, 0, ',', '.') }}</span></div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-muted">{{ __('Noch keine Aufrufe. Drucken Sie den QR-Code einer Seite aus und stellen Sie ihn auf den Tisch.') }}</p>
            @endif
        </section>

        <section class="card p-6">
            <h2 class="font-semibold">{{ __('Bestellungen je Tag') }}</h2>
            <div class="mt-5">{!! $bars($orderSeries, $labels) !!}</div>
            <h3 class="mt-6 text-sm font-semibold">{{ __('Umsatz je Tag') }}</h3>
            <div class="mt-3">{!! $bars($revenueSeries, $labels, $euro) !!}</div>
        </section>

        <section class="card p-6">
            <h2 class="font-semibold">{{ __('Beliebteste Gerichte') }}</h2>
            @if ($topItems)
                @php $maxQty = max(array_column($topItems, 'qty')); @endphp
                <div class="mt-4 space-y-3">
                    @foreach ($topItems as $name => $it)
                        <div>
                            <div class="flex justify-between gap-3 text-sm"><span class="truncate font-medium">{{ $name }}</span><span class="shrink-0 tabular-nums text-muted">{{ $it['qty'] }}× · {{ $euro($it['sum']) }}</span></div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-sand"><div class="h-full rounded-full bg-ink/85" style="width: {{ round($it['qty'] / $maxQty * 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-muted">{{ __('Sobald Gäste per Handy bestellen, sehen Sie hier, was am besten läuft.') }}</p>
            @endif
        </section>

        <section class="card p-6">
            <h2 class="font-semibold">{{ __('Wann wird bestellt?') }}</h2>
            @if ($ordersTotal)
                @php $hl = array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)); @endphp
                <div class="mt-5">{!! $bars($hours, $hl) !!}</div>
            @else
                <p class="mt-4 text-sm text-muted">{{ __('Hier erscheinen die stärksten Stunden des Tages.') }}</p>
            @endif

            <h2 class="mt-8 font-semibold">{{ __('Laufzeit der Bildschirme') }}</h2>
            @forelse ($screens as $s)
                @php $u = $uptime[$s->id] ?? null; $hrs = $u ? $u->s / 3600 : 0; @endphp
                <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $s->isOnline() ? 'bg-green-500' : 'bg-red-500' }}"></span>{{ $s->name }}</span>
                    <span class="tabular-nums text-muted">{{ __(':h Std. an :d Tagen', ['h' => number_format($hrs, $hrs < 10 ? 1 : 0, ',', '.'), 'd' => $u->d ?? 0]) }}</span>
                </div>
            @empty
                <p class="mt-3 text-sm text-muted">{{ __('Noch kein Bildschirm verbunden.') }}</p>
            @endforelse
        </section>
    </div>
</div>
