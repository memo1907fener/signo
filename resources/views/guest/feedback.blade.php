@php $accent = $org->brandAccent() ?? '#ff5a36'; @endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $t['fb_question'] }} · {{ $org->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
    <style>
        .star { font-size: 46px; line-height: 1; background: none; border: 0; cursor: pointer; color: rgba(18, 23, 38, .16); transition: transform .15s, color .15s; padding: 2px; }
        .star.on { color: #f5a623; }
        .star:active { transform: scale(1.25); }
        .go { background: {{ $accent }}; }
    </style>
</head>
<body class="min-h-screen bg-paper font-sans text-ink">
<div class="mx-auto flex min-h-screen max-w-md flex-col px-5 py-10 text-center" x-data="signoFeedback()">
    @if ($org->showsLogo())
        <img src="{{ $org->logoUrl() }}" alt="{{ $org->name }}" class="mx-auto max-h-16 max-w-[220px] object-contain">
    @else
        <p class="text-sm font-semibold uppercase tracking-widest text-muted">{{ $org->name }}</p>
    @endif

    <div x-show="!done" class="mt-10">
        <h1 class="text-3xl font-bold tracking-tight">{{ $t['fb_question'] }}</h1>
        <div class="mt-7 flex justify-center gap-1" role="radiogroup" aria-label="{{ $t['fb_question'] }}">
            @foreach (range(1, 5) as $n)
                <button type="button" class="star" :class="rating >= {{ $n }} && 'on'" @click="rating = {{ $n }}; error = ''" role="radio" :aria-checked="rating === {{ $n }}" aria-label="{{ $n }}">★</button>
            @endforeach
        </div>

        <div x-show="rating > 0" x-cloak class="mt-8 text-left">
            <label class="label" for="c" x-text="rating <= 3 ? @js($t['fb_improve']) : @js($t['fb_comment'])"></label>
            <textarea id="c" x-model="comment" rows="4" maxlength="1000" class="input"></textarea>
            <div x-show="rating <= 3" class="mt-4">
                <label class="label" for="k">{{ $t['fb_contact'] }}</label>
                <input id="k" x-model="contact" maxlength="120" class="input">
            </div>
            <input type="text" x-model="website" class="absolute -left-[9999px]" tabindex="-1" autocomplete="off" aria-hidden="true">
        </div>
        <p class="error mt-4" x-show="error" x-text="error"></p>
        <button type="button" class="go mt-6 w-full rounded-2xl py-4 text-base font-bold text-white disabled:opacity-50" :disabled="busy" @click="send()">{{ $t['fb_send'] }}</button>
    </div>

    <div x-show="done" x-cloak class="mt-14">
        <div class="text-6xl">🙏</div>
        <h1 class="mt-4 text-3xl font-bold tracking-tight">{{ $t['fb_thanks'] }}</h1>
        <p class="mt-2 text-muted">{{ $t['fb_thanks_text'] }}</p>
        @if ($org->google_review_url)
            {{-- Der Link wird allen Gästen angeboten, unabhängig von der Bewertung --}}
            <div class="mt-8 rounded-3xl bg-white p-6 ring-1 ring-black/5">
                <p class="text-sm text-muted">{{ $t['fb_google_text'] }}</p>
                <a href="{{ $org->google_review_url }}" target="_blank" rel="noopener" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl py-3.5 font-bold"
                   :class="rating >= 4 ? 'go text-white' : 'bg-white text-ink ring-1 ring-black/15'">{{ $t['fb_google'] }}</a>
            </div>
        @endif
    </div>
    <p class="mt-auto pt-10 text-xs text-muted"><a href="{{ url('/') }}">Signo</a></p>
</div>
<script>
    function signoFeedback() {
        return {
            rating: 0, comment: '', contact: '', website: '', busy: false, done: false, error: '',
            async send() {
                if (!this.rating) { this.error = @js($t['fb_pick']); return; }
                this.busy = true; this.error = '';
                try {
                    const r = await fetch(@json(route('feedback.store', $token)), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ rating: this.rating, comment: this.comment, contact: this.contact, website: this.website, lang: @json($lang) }),
                    });
                    if (r.ok) { this.done = true; } else { const j = await r.json().catch(() => ({})); this.error = j.message || @js($t['error']); }
                } catch (e) { this.error = @js($t['error']); }
                this.busy = false;
            },
        };
    }
</script>
@livewireScripts
</body>
</html>
