{{-- Eine Signo-Seite. $mode: tv | mobile --}}
@php
    $d = $page->data ?? [];
    $mode = $mode ?? 'tv';
    $img = fn ($p) => \App\Models\Page::fileUrl($p);
    $ordering = $ordering ?? false;
    $orderPrice = fn ($p) => $ordering ? \App\Models\Order::parsePrice($p) : null;
    $L = $page->lang ?? 'de';
    $ui = \App\Support\Ui::mobile($L);
    $tagLabels = \App\Support\Ui::tagLabels($L);
    $brandOrg = $page->organization;
    $logo = $brandOrg && $brandOrg->showsLogo() && $mode === 'tv' ? $brandOrg->logoUrl() : null;
    $legendTags = ! empty($d['legend']) ? array_values(array_filter($page->usedTags(), fn ($t) => strlen($t) === 1)) : [];
@endphp
<div class="sg-page sg-{{ $mode }} sg-theme-{{ $page->theme }} sg-font-{{ $page->font }} sg-t-{{ $page->template }} {{ ! empty($d['ticker']) && $mode === 'tv' ? 'sg-has-ticker' : '' }}"
     style="--accent: {{ $page->accentColor() }}; --bg: {{ $page->colors()[0] }}; --fg: {{ $page->colors()[1] }};" data-revision="{{ $page->revision() }}">
    <div class="sg-fit">

    @if ($page->template === 'list')
        @php
            $sections = collect($d['sections'] ?? [])->map(function ($s, $si) {
                $s['items'] = collect($s['items'] ?? [])->map(fn ($i, $ii) => $i + ['_s' => $si, '_i' => $ii])
                    ->filter(fn ($i) => trim(($i['name'] ?? '') . ($i['price'] ?? '')) !== '')->values()->all();
                return $s;
            })->filter(fn ($s) => count($s['items']) || trim($s['title'] ?? '') !== '')->values();
            $count = $sections->sum(fn ($s) => count($s['items']) + (trim($s['title'] ?? '') !== '' ? 1.5 : 0));
            $cols = $count > 16 ? 3 : ($count > 7 ? 2 : 1);
        @endphp
        <header class="sg-head {{ $logo ? 'sg-with-logo' : '' }}">
            @if ($logo)<img class="sg-logo" src="{{ $logo }}" alt="">@endif
            <h1 class="sg-h1">{{ $d['heading'] ?? '' }}</h1>
            @if (! empty($d['subheading']))
                <p class="sg-sub">{{ $d['subheading'] }}</p>
            @endif
            @if ($page->happyActive())
                <span class="sg-kicker sg-happy-badge">{{ $d['happy']['label'] ?? 'Happy Hour' }} · {{ $ui['until'] }} {{ $d['happy']['end'] }}</span>
            @endif
        </header>
        <div class="sg-list" style="--cols: {{ $cols }}">
            @foreach ($sections as $sIdx => $s)
                <section class="sg-section">
                    @if (trim($s['title'] ?? '') !== '')
                        <h2 class="sg-h2">{{ $s['title'] }}</h2>
                    @endif
                    @foreach ($s['items'] as $it)
                        @include('display.item')
                    @endforeach
                </section>
            @endforeach
        </div>
        @include('display.foot')

    @elseif ($page->template === 'week')
        @php
            $todayIdx = now()->timezone(config('app.timezone'))->dayOfWeekIso - 1;
            $days = collect($d['days'] ?? [])->map(function ($day, $n) {
                $day['n'] = $n;
                $day['items'] = collect($day['items'] ?? [])->filter(fn ($i) => trim($i['name'] ?? '') !== '')->values()->all();
                return $day;
            })->filter(fn ($day) => count($day['items']))->values();
            $today = $days->firstWhere('n', $todayIdx);
            $single = $mode === 'tv' && ($d['view'] ?? 'today') === 'today' && $today;
        @endphp
        <header class="sg-head {{ $logo ? 'sg-with-logo' : '' }}">
            @if ($logo)<img class="sg-logo" src="{{ $logo }}" alt="">@endif
            <h1 class="sg-h1">{{ $d['heading'] ?? '' }}</h1>
            @if ($single)
                <p class="sg-sub">{{ $ui['today'] }}, {{ \App\Support\Ui::weekday($todayIdx, $L) }}{{ ! empty($d['subheading']) ? ' · ' . $d['subheading'] : '' }}</p>
            @elseif (! empty($d['subheading']))
                <p class="sg-sub">{{ $d['subheading'] }}</p>
            @endif
            @if (! empty($d['price']))
                <span class="sg-kicker sg-happy-badge">{{ $ui['menu'] }} {{ $d['price'] }}</span>
            @endif
        </header>
        @if ($days->isEmpty())
            <p class="sg-text">{{ $ui['no_menu'] }}</p>
        @elseif ($single)
            <div class="sg-list sg-week-today" style="--cols: 1">
                <section class="sg-section">
                    @foreach ($today['items'] as $it)
                        @include('display.item')
                    @endforeach
                </section>
            </div>
        @else
            <div class="sg-week" style="--days: {{ $days->count() }}">
                @foreach ($days as $day)
                    <section class="sg-day {{ $day['n'] === $todayIdx ? 'sg-day-today' : '' }}">
                        <h2 class="sg-h2">{{ \App\Support\Ui::weekday($day['n'], $L) }}@if ($day['n'] === $todayIdx)<span class="sg-day-flag">{{ $ui['today'] }}</span>@endif</h2>
                        @foreach ($day['items'] as $it)
                            @include('display.item')
                        @endforeach
                    </section>
                @endforeach
            </div>
        @endif
        @include('display.foot')

    @elseif ($page->template === 'offer')
        <div class="sg-split {{ empty($d['image']) ? 'sg-noimg' : '' }}">
            <div class="sg-split-text">
                @if (! empty($d['kicker']))
                    <span class="sg-kicker">{{ $d['kicker'] }}</span>
                @endif
                <h1 class="sg-h1 sg-huge">{{ $d['heading'] ?? '' }}</h1>
                @if (! empty($d['text']))
                    <p class="sg-text">{!! nl2br(e($d['text'])) !!}</p>
                @endif
                @if (! empty($d['price']) || ! empty($d['old_price']))
                    <div class="sg-price">
                        @if (! empty($d['old_price']))
                            <s class="sg-old">{{ $d['old_price'] }}</s>
                        @endif
                        <span>{{ $d['price'] ?? '' }}</span>
                    </div>
                @endif
                @if (! empty($d['footer']))
                    <p class="sg-foot">{{ $d['footer'] }}</p>
                @endif
            </div>
            @if (! empty($d['image']))
                <div class="sg-split-img"><img src="{{ $img($d['image']) }}" alt=""></div>
            @else
                <div class="sg-deco" aria-hidden="true"></div>
            @endif
        </div>

    @elseif ($page->template === 'info')
        <div class="sg-split {{ empty($d['image']) ? 'sg-noimg' : '' }}">
            <div class="sg-split-text">
                <h1 class="sg-h1">{{ $d['heading'] ?? '' }}</h1>
                @if (! empty($d['text']))
                    <p class="sg-text">{!! nl2br(e($d['text'])) !!}</p>
                @endif
                @php $rows = collect($d['rows'] ?? [])->filter(fn ($r) => trim(($r['label'] ?? '') . ($r['value'] ?? '')) !== ''); @endphp
                @if ($rows->count())
                    <div class="sg-rows">
                        @foreach ($rows as $r)
                            <div class="sg-row"><span>{{ $r['label'] ?? '' }}</span><strong>{{ $r['value'] ?? '' }}</strong></div>
                        @endforeach
                    </div>
                @endif
                @if (! empty($d['footer']))
                    <p class="sg-foot">{{ $d['footer'] }}</p>
                @endif
            </div>
            @if (! empty($d['image']))
                <div class="sg-split-img"><img src="{{ $img($d['image']) }}" alt=""></div>
            @endif
        </div>

    @elseif ($page->template === 'clock')
        <div class="sg-clock">
            @if (! empty($d['heading']))<p class="sg-clock-head">{{ $d['heading'] }}</p>@endif
            <div class="sg-clock-time" data-clock>{{ now()->format('H:i') }}</div>
            <div class="sg-clock-date" data-date="{{ $page->lang ?? 'de' }}">{{ now()->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}</div>
            @if (! empty($d['weather']) && ! empty($d['city']))
                <div class="sg-weather" data-weather="{{ $d['city'] }}"><span class="sg-w-icon"></span><span class="sg-w-temp"></span><span class="sg-w-city">{{ $d['city'] }}</span></div>
            @endif
            @if (! empty($d['text']))<p class="sg-text">{{ $d['text'] }}</p>@endif
        </div>

    @elseif ($page->template === 'countdown')
        <div class="sg-clock">
            @if (! empty($d['heading']))<h1 class="sg-h1">{{ $d['heading'] }}</h1>@endif
            <div class="sg-count" data-countdown="{{ $d['target'] ?? '' }}" data-done="{{ $d['done_text'] ?? '' }}">
                @foreach (['d' => 'Tage', 'h' => 'Std.', 'm' => 'Min.', 's' => 'Sek.'] as $k => $lbl)
                    <div class="sg-count-box"><span data-part="{{ $k }}">–</span><small>{{ $lbl }}</small></div>
                @endforeach
            </div>
            @if (! empty($d['text']))<p class="sg-text">{{ $d['text'] }}</p>@endif
        </div>

    @elseif ($page->template === 'orders')
        @php $tok = $page->organization?->kitchen_token; @endphp
        <div class="sg-media">
            @if ($tok)
                <iframe src="{{ route('board', ['token' => $tok, 'theme' => $page->theme]) }}" style="border:0;width:100%;height:100%;display:block" title="Abholanzeige"></iframe>
            @else
                <div class="sg-empty">Bestellen ist noch nicht eingeschaltet</div>
            @endif
        </div>

    @elseif ($page->template === 'media')
        @php $src = $img($d['file'] ?? null); $isVideo = str_starts_with((string) ($d['mime'] ?? ''), 'video/'); @endphp
        <div class="sg-media sg-fit-{{ $d['fit'] ?? 'cover' }}">
            @if (! $src)
                <div class="sg-empty">Noch kein Bild oder Video ausgewählt</div>
            @elseif ($isVideo)
                <video src="{{ $src }}" muted playsinline loop autoplay preload="auto" disablepictureinpicture></video>
            @else
                <img src="{{ $src }}" alt="">
            @endif
            @if (! empty($d['caption']))
                <div class="sg-caption">{{ $d['caption'] }}</div>
            @endif
        </div>
    @endif

    </div>
    @if (! empty($d['ticker']) && $mode === 'tv')
        <div class="sg-ticker"><div class="sg-ticker-track" style="--len: {{ max(20, mb_strlen($d['ticker'])) }}"><span>{{ $d['ticker'] }}</span><span aria-hidden="true">{{ $d['ticker'] }}</span></div></div>
    @endif
</div>
