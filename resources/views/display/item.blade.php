{{-- Ein Eintrag mit Preis, Kennzeichen, "ausverkauft" und Aktionspreis --}}
@php
    [$price, $old] = $page->priceOf($it);
    $sold = \App\Models\Page::soldOut($it);
    $tags = $it['tags'] ?? [];
    $letters = array_values(array_filter($tags, fn ($t) => strlen($t) === 1));
    $marks = array_values(array_filter($tags, fn ($t) => strlen($t) > 1));
@endphp
<div class="sg-item {{ $sold ? 'sg-sold' : '' }}" data-tags="{{ implode(' ', $tags) }}">
    <div class="sg-item-row">
        <span class="sg-item-name">{{ $it['name'] ?? '' }}@foreach ($marks as $m)<span class="sg-mark" title="{{ $tagLabels[$m] ?? '' }}">{{ config("signo.marks.$m.icon") }}</span>@endforeach @if ($letters)<sup class="sg-alg">{{ implode(',', $letters) }}</sup>@endif</span>
        <span class="sg-dots"></span>
        @if ($sold)
            <span class="sg-soldtag">{{ $ui['soldout'] }}</span>
        @else
            @if ($old)<s class="sg-item-old">{{ $old }}</s>@endif
            <span class="sg-item-price {{ $old ? 'sg-happy' : '' }}">{{ $price }}</span>
            @if (isset($it['_s']) && ($cents = $orderPrice($price)))
                <button type="button" class="sg-add" @click="add({{ $it['_s'] }}, {{ $it['_i'] }}, @js($it['name'] ?? ''), {{ $cents }})" aria-label="+">+</button>
            @endif
        @endif
    </div>
    @if (! empty($it['description']))
        <div class="sg-item-desc">{{ $it['description'] }}</div>
    @endif
</div>
