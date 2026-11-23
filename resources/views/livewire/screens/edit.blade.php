@php $on = $screen->isOnline(); $sleep = $on && $screen->isSleeping(); @endphp
<div>
    <a href="{{ route('screens.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">{{ __('← Alle Bildschirme') }}</a>

    <div class="mt-4 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <div>
                <input wire:model.live.debounce.700ms="name" class="w-full bg-transparent text-3xl font-bold tracking-tight outline-none">
                @error('name') <p class="error">{{ $message }}</p> @enderror
                <p class="mt-1 flex items-center gap-2 text-sm text-muted">
                    <span class="h-2.5 w-2.5 rounded-full {{ ! $on ? 'bg-red-500' : ($sleep ? 'bg-amber-400' : 'bg-green-500') }}"></span>
                    {{ $screen->statusLabel() }}
                </p>
            </div>

            {{-- Inhalte --}}
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('Was soll angezeigt werden?') }}</h2>
                <div class="mt-4 inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                    <button wire:click="$set('showAll', true)" class="rounded-full px-4 py-1.5 {{ $showAll ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Alle Seiten') }}</button>
                    <button wire:click="$set('showAll', false)" class="rounded-full px-4 py-1.5 {{ ! $showAll ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Auswahl') }}</button>
                </div>

                @if ($showAll)
                    <p class="mt-3 text-sm text-muted">{{ __('Alle :n Seiten laufen nacheinander in der Reihenfolge von „Inhalte“.', ['n' => $pages->count()]) }}</p>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach ($selected as $idx => $id)
                            @php $p = $pages->firstWhere('id', $id); @endphp
                            @if ($p)
                                <div class="flex items-center gap-3 rounded-2xl bg-paper px-4 py-2.5 ring-1 ring-ink/20" wire:key="sel-{{ $id }}">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">{{ $idx + 1 }}</span>
                                    <span class="flex-1 font-medium">{{ $p->title }}</span>
                                    <button wire:click="moveSelected({{ $id }}, -1)" class="btn-ghost btn-sm">↑</button>
                                    <button wire:click="moveSelected({{ $id }}, 1)" class="btn-ghost btn-sm">↓</button>
                                    <button wire:click="togglePage({{ $id }})" class="btn-ghost btn-sm">{{ __('Entfernen') }}</button>
                                </div>
                            @endif
                        @endforeach
                        @foreach ($pages->whereNotIn('id', $selected) as $p)
                            <button wire:click="togglePage({{ $p->id }})" class="flex w-full items-center gap-3 rounded-2xl px-4 py-2.5 text-left ring-1 ring-black/10 hover:ring-black/25" wire:key="un-{{ $p->id }}">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full ring-1 ring-black/20 text-xs">+</span>
                                <span class="flex-1 text-muted">{{ $p->title }}</span>
                            </button>
                        @endforeach
                        @if (! count($selected))
                            <p class="text-sm text-amber-700">{{ __('Keine Seite gewählt – der Bildschirm zeigt nur Ihr Logo-Standbild.') }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-6 flex items-center gap-3">
                    <label class="text-sm font-medium" for="sec">{{ __('Anzeigedauer je Seite') }}</label>
                    <input id="sec" type="number" min="4" max="600" wire:model.live.debounce.700ms="seconds" class="input w-24 py-1.5 text-center">
                    <span class="text-sm text-muted">{{ __('Sekunden') }}</span>
                </div>
                @error('seconds') <p class="error">{{ $message }}</p> @enderror
            </div>

            {{-- Betriebszeiten --}}
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('Betriebszeiten') }}</h2>
                <div class="mt-4 inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                    <button wire:click="$set('alwaysOn', true)" class="rounded-full px-4 py-1.5 {{ $alwaysOn ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Immer an') }}</button>
                    <button wire:click="$set('alwaysOn', false)" class="rounded-full px-4 py-1.5 {{ ! $alwaysOn ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Zu festen Zeiten') }}</button>
                </div>
                @if (! $alwaysOn)
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                        <span>{{ __('von') }}</span>
                        <input type="time" wire:model.live.debounce.700ms="onTime" class="input w-32 py-1.5">
                        <span>{{ __('bis') }}</span>
                        <input type="time" wire:model.live.debounce.700ms="offTime" class="input w-32 py-1.5">
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $n => $lbl)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model.live="days" value="{{ $n }}" class="peer sr-only">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium ring-1 ring-black/10 peer-checked:bg-ink peer-checked:text-white">{{ __($lbl) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="hint mt-3">{{ __('Außerhalb dieser Zeiten wird der Bildschirm schwarz geschaltet (schont Gerät und Strom).') }}</p>
                @endif
            </div>
        </div>

        {{-- Seitenleiste --}}
        <div class="space-y-6" wire:poll.15s>
            <div class="card overflow-hidden">
                <div class="flex aspect-video items-center justify-center bg-ink">
                    @if ($screen->screenshot_path)
                        <img src="{{ \App\Models\Page::fileUrl($screen->screenshot_path) }}?t={{ $screen->screenshot_at?->timestamp }}" class="h-full w-full object-cover" alt="{{ __('Bildschirmfoto') }}">
                    @else
                        <span class="px-6 text-center text-sm text-white/50">{{ __($screen->client === 'android' ? 'Noch kein Bildschirmfoto' : 'Bildschirmfotos gibt es mit der Signo-App für Android') }}</span>
                    @endif
                </div>
                <div class="p-5 text-sm">
                    @if ($screen->screenshot_at)<p class="text-muted">{{ __('Foto vom :date', ['date' => $screen->screenshot_at->format('d.m. H:i')]) }}</p>@endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($screen->client === 'android')
                            <button wire:click="command('screenshot')" class="btn-ghost btn-sm">{{ __('Foto anfordern') }}</button>
                        @endif
                        <button wire:click="command('reload')" class="btn-ghost btn-sm">{{ __('Neu laden') }}</button>
                        @if ($screen->client === 'android')
                            <button wire:click="command('restart_app')" class="btn-ghost btn-sm">{{ __('App neu starten') }}</button>
                        @endif
                    </div>
                    @if ($flash)<p class="mt-3 text-green-700">{{ $flash }}</p>@endif
                </div>
            </div>

            <div class="card p-5 text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Gerät') }}</dt><dd>{{ $screen->client === 'android' ? 'Android-App' : 'Browser' }}{{ $screen->app_version ? ' (v' . $screen->app_version . ')' : '' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Auflösung') }}</dt><dd>{{ $screen->resolution ?: '–' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Zuletzt gesehen') }}</dt><dd>{{ $screen->last_seen_at?->diffForHumans() ?? 'nie' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('Verbunden seit') }}</dt><dd>{{ $screen->paired_at?->format('d.m.Y') }}</dd></div>
                </dl>
            </div>

            <button wire:click="unpair" wire:confirm="{{ __('Bildschirm „:name“ wirklich entfernen?', ['name' => $screen->name]) }}" class="btn-danger w-full">{{ __('Bildschirm entfernen') }}</button>
        </div>
    </div>
</div>
