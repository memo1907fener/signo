{{-- Eine Signo-Seite. $mode: tv | mobile --}}
@php
    $d = $page->data ?? [];
    $mode = $mode ?? 'tv';
    $img = fn ($p) => \App\Models\Page::fileUrl($p);
@endphp
<div class="sg-page sg-{{ $mode }} sg-theme-{{ $page->theme }} sg-font-{{ $page->font }} sg-t-{{ $page->template }}"
     style="--accent: {{ $page->accentColor() }};" data-revision="{{ $page->revision() }}">
    <div class="sg-fit">

    @if ($page->template === 'list')
        @php
            $sections = collect($d['sections'] ?? [])->map(function ($s) {
                $s['items'] = collect($s['items'] ?? [])->filter(fn ($i) => trim(($i['name'] ?? '') . ($i['price'] ?? '')) !== '')->values()->all();
                return $s;
            })->filter(fn ($s) => count($s['items']) || trim($s['title'] ?? '') !== '')->values();
            $count = $sections->sum(fn ($s) => count($s['items']) + (trim($s['title'] ?? '') !== '' ? 1.5 : 0));
            $cols = $count > 16 ? 3 : ($count > 7 ? 2 : 1);
        @endphp
        <header class="sg-head">
            <h1 class="sg-h1">{{ $d['heading'] ?? '' }}</h1>
            @if (! empty($d['subheading']))
                <p class="sg-sub">{{ $d['subheading'] }}</p>
            @endif
        </header>
        <div class="sg-list" style="--cols: {{ $cols }}">
            @foreach ($sections as $s)
                <section class="sg-section">
                    @if (trim($s['title'] ?? '') !== '')
                        <h2 class="sg-h2">{{ $s['title'] }}</h2>
                    @endif
                    @foreach ($s['items'] as $it)
                        <div class="sg-item">
                            <div class="sg-item-row">
                                <span class="sg-item-name">{{ $it['name'] ?? '' }}</span>
                                <span class="sg-dots"></span>
                                <span class="sg-item-price">{{ $it['price'] ?? '' }}</span>
                            </div>
                            @if (! empty($it['description']))
                                <div class="sg-item-desc">{{ $it['description'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>
        @if (! empty($d['footer']))
            <footer class="sg-foot">{{ $d['footer'] }}</footer>
        @endif

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
</div>
