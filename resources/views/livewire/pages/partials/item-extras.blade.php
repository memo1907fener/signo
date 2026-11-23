{{-- Kennzeichen, "Heute aus" und Aktionspreis eines Eintrags. Erwartet: $path, $it, $happyOn --}}
@php
    $tags = $it['tags'] ?? [];
    $sold = \App\Models\Page::soldOut($it);
    $summary = collect($tags)->map(fn ($t) => strlen($t) === 1 ? $t : config("signo.marks.$t.icon"))->implode(' ');
@endphp
<div class="col-span-full flex flex-wrap items-center gap-2 text-xs">
    <div x-data="{ o: false }" class="relative">
        <button type="button" @click="o = !o" class="chip bg-paper ring-1 ring-black/10 hover:ring-black/30">{{ $summary !== '' ? $summary : __('Allergene & Kennzeichen') }}</button>
        <div x-show="o" x-cloak @click.outside="o = false" class="absolute left-0 z-30 mt-1 w-72 rounded-2xl bg-white p-3 shadow-xl ring-1 ring-black/10">
            <div class="flex flex-wrap gap-1.5">
                @foreach (config('signo.marks') as $code => $m)
                    <button type="button" wire:click="toggleTag('{{ $path }}', '{{ $code }}')" class="chip ring-1 {{ in_array($code, $tags) ? 'bg-ink text-white ring-ink' : 'bg-white ring-black/10' }}">{{ $m['icon'] }} {{ __($m['label']) }}</button>
                @endforeach
            </div>
            <p class="mb-1.5 mt-3 font-semibold text-muted">{{ __('Enthält') }}</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach (config('signo.allergens') as $code => $label)
                    <button type="button" wire:click="toggleTag('{{ $path }}', '{{ $code }}')" class="chip ring-1 {{ in_array($code, $tags) ? 'bg-ink text-white ring-ink' : 'bg-white ring-black/10' }}"><b>{{ $code }}</b> {{ __($label) }}</button>
                @endforeach
            </div>
        </div>
    </div>
    <button type="button" wire:click="toggleSoldOut('{{ $path }}')" class="chip ring-1 {{ $sold ? 'bg-red-600 text-white ring-red-600' : 'bg-paper ring-black/10 hover:ring-black/30' }}">{{ $sold ? __('Heute aus – wieder da?') : __('Heute aus') }}</button>
    @if ($happyOn ?? false)
        <input wire:model.live.debounce.500ms="data.{{ $path }}.happy" class="w-28 rounded-full border-0 bg-amber-50 px-3 py-1 text-right text-xs tabular-nums ring-1 ring-amber-300 focus:outline-none focus:ring-2 focus:ring-ink" placeholder="{{ __('Aktionspreis') }}">
    @endif
</div>
