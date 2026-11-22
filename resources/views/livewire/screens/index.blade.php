<div wire:poll.30s>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Bildschirme</h1>
            <p class="mt-1 text-muted">{{ $screens->count() }} von {{ $org->screenLimit() }} {{ $org->screenLimit() === 1 ? 'Bildschirm' : 'Bildschirmen' }} in Ihrem Paket.</p>
        </div>
        <button wire:click="$set('adding', true)" class="btn-brand">+ Bildschirm verbinden</button>
    </div>

    @if ($adding || $screens->isEmpty())
        <div class="card mt-6 grid gap-8 p-6 sm:p-8 md:grid-cols-2">
            <div>
                <h2 class="text-lg font-bold">So verbinden Sie einen Bildschirm</h2>
                <ol class="mt-4 space-y-4 text-sm">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">1</span>
                        <span>Am Fernseher oder an der TV-Box den Browser öffnen und <strong class="whitespace-nowrap">{{ str_replace(['https://', 'http://'], '', url('/tv')) }}</strong> eingeben – oder die Signo-App für Android starten.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">2</span>
                        <span>Auf dem Bildschirm erscheint ein Code mit 6 Zeichen.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">3</span>
                        <span>Code hier eingeben – fertig.</span></li>
                </ol>
            </div>
            <form wire:submit="pair" class="space-y-4">
                <div>
                    <label class="label" for="code">Code vom Bildschirm</label>
                    <input id="code" wire:model="code" class="input text-center font-mono text-2xl uppercase tracking-[.3em]" maxlength="7" placeholder="ABC123" autocomplete="off">
                    @error('code') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="sname">Name</label>
                    <input id="sname" wire:model="name" class="input" placeholder="z. B. Fenster, Theke, Wartezimmer">
                </div>
                <button class="btn-primary w-full py-3">Verbinden</button>
            </form>
        </div>
    @endif

    @if ($screens->isNotEmpty())
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($screens as $s)
                @php $on = $s->isOnline(); $sleep = $on && $s->isSleeping(); @endphp
                <a href="{{ route('screens.edit', $s) }}" wire:navigate class="card block overflow-hidden transition hover:ring-black/20" wire:key="s-{{ $s->id }}">
                    <div class="flex aspect-video items-center justify-center bg-ink/95">
                        @if ($s->screenshot_path)
                            <img src="{{ \App\Models\Page::fileUrl($s->screenshot_path) }}?t={{ $s->screenshot_at?->timestamp }}" class="h-full w-full object-cover" alt="">
                        @else
                            <span class="text-5xl opacity-60">🖥️</span>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ ! $on ? 'bg-red-500' : ($sleep ? 'bg-amber-400' : 'bg-green-500') }}"></span>
                            <span class="font-semibold">{{ $s->name }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted">{{ $s->statusLabel() }} · {{ $s->hoursLabel() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
