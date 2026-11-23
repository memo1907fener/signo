@php
    $k = $kitchen;
    $card = $k ? 'rounded-3xl bg-white/[.06] ring-1 ring-white/10' : 'card';
    $muted = $k ? 'text-white/55' : 'text-muted';
    $kitchenUrl = $org->kitchen_token ? route('kitchen', $org->kitchen_token) : null;
    $boardUrl = $org->kitchen_token ? route('board', $org->kitchen_token) : null;
@endphp
<div>
    @if (! $k)
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">{{ __('Bestellungen') }}</h1>
                <p class="mt-1 text-muted">{{ __('Gäste bestellen per QR-Code am Handy – die Bestellung erscheint hier und in der Küche.') }}</p>
            </div>
            @if ($org->ordering_enabled)
                <div class="text-right text-sm text-muted">{{ __('Heute') }}: <strong class="text-ink">{{ $todayCount }}</strong> {{ __('Bestellungen') }} · <strong class="text-ink">{{ \App\Models\Order::euro($today) }}</strong></div>
            @endif
        </div>

        {{-- Einstellungen --}}
        <div class="card mt-6 p-6" x-data="{ open: {{ $org->ordering_enabled ? 'false' : 'true' }} }">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <label class="flex cursor-pointer items-center gap-3">
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" wire:model.live="enabled" class="peer sr-only">
                        <span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-green-600"></span>
                        <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                    <span class="font-semibold">{{ __($enabled ? 'Bestellen ist eingeschaltet' : 'Bestellen per Handy einschalten') }}</span>
                </label>
                <button @click="open = !open" class="text-sm text-muted hover:text-ink" x-text="open ? @js(__('Einstellungen ausblenden')) : @js(__('Einstellungen'))"></button>
            </div>

            <div x-show="open" x-cloak class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <span class="label">{{ __('Wie wird bestellt?') }}</span>
                    <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                        <button wire:click="$set('mode', 'pickup')" class="rounded-full px-4 py-1.5 {{ $mode === 'pickup' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Zum Abholen (Name)') }}</button>
                        <button wire:click="$set('mode', 'table')" class="rounded-full px-4 py-1.5 {{ $mode === 'table' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Am Tisch (Tischnummer)') }}</button>
                    </div>
                    <label class="label mt-5" for="onote">{{ __('Hinweis für Gäste') }}</label>
                    <input id="onote" wire:model="note" wire:blur="saveSettings" class="input" placeholder="{{ __('z. B. Bezahlung an der Kassa') }}">
                    <p class="hint">{{ __('Bezahlt wird wie gewohnt vor Ort.') }} @if ($saved) <span class="text-green-700">{{ __('Gespeichert.') }}</span> @endif</p>

                    <p class="label mt-5">{{ __('Bestellbare Seiten') }}</p>
                    @forelse ($orderPages as $p)
                        <a href="{{ route('pages.edit', $p) }}" wire:navigate class="mr-2 inline-block rounded-full bg-paper px-3 py-1 text-sm ring-1 ring-black/10">{{ $p->title }}</a>
                    @empty
                        <p class="text-sm text-muted">{{ __('Noch keine. In einer Liste mit Preisen „Per Handy bestellbar“ einschalten.') }}</p>
                    @endforelse
                </div>

                @if ($org->kitchen_token)
                    <div class="space-y-5">
                        <div>
                            <p class="label">{{ __('Küchenbildschirm (Tablet in der Küche)') }}</p>
                            <div class="flex gap-2">
                                <input readonly value="{{ $kitchenUrl }}" class="input text-xs" onclick="this.select()">
                                <a href="{{ $kitchenUrl }}" target="_blank" class="btn-ghost btn-sm shrink-0">{{ __('Öffnen') }}</a>
                            </div>
                            <p class="hint">{{ __('Ohne Anmeldung nutzbar – den Link nicht weitergeben.') }} <button wire:click="newToken" wire:confirm="{{ __('Neuen Link erzeugen? Der alte Küchen- und Abhol-Link funktioniert dann nicht mehr.') }}" class="underline">{{ __('Neuen Link erzeugen') }}</button></p>
                        </div>
                        <div>
                            <p class="label">{{ __('Abholanzeige für Gäste') }}</p>
                            @if ($hasBoard)
                                <p class="text-sm text-muted">{{ __('Die Seite „Abholanzeige“ ist angelegt – unter Bildschirme auswählen.') }}</p>
                            @else
                                <button wire:click="createBoardPage" class="btn-ghost btn-sm">{{ __('Als Seite für einen Bildschirm anlegen') }}</button>
                            @endif
                            <a href="{{ $boardUrl }}" target="_blank" class="ml-2 text-sm underline">{{ __('Vorschau') }}</a>
                        </div>
                        @if ($mode === 'table')
                            <div x-data="{ t: '' }">
                                <p class="label">{{ __('QR-Code für einen Tisch') }}</p>
                                <div class="flex gap-2">
                                    <input x-model="t" class="input w-28" placeholder="{{ __('Tisch-Nr.') }}">
                                    @if ($orderPages->first())
                                        <button class="btn-primary btn-sm" @click="t && signoQrDownload('{{ $orderPages->first()->publicUrl() }}?tisch=' + encodeURIComponent(t), 'tisch-' + t)">{{ __('QR herunterladen') }}</button>
                                    @endif
                                </div>
                                <p class="hint">{{ __('Die Tischnummer ist beim Bestellen dann schon ausgefüllt.') }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-logo :dark="true" />
                <span class="text-white/50">Küche · {{ $org->name }}</span>
            </div>
            <span class="text-sm text-white/50">{{ now()->format('H:i') }}</span>
        </div>
    @endif

    @if ($org->ordering_enabled || $k)
        <div wire:poll.5s x-data="signoKitchen({{ $lastId }})" x-init="watch({{ $lastId }})" data-last="{{ $lastId }}" class="{{ $k ? '' : 'mt-8' }}">
            <div class="mb-3 flex justify-end">
                <button @click="enable()" class="text-sm {{ $muted }} hover:underline" x-text="sound ? '🔔 Ton ist an' : '🔕 Ton für neue Bestellungen einschalten'"></button>
            </div>
            <div class="grid gap-5 lg:grid-cols-3">
                @foreach (['new' => 'Neu', 'preparing' => 'In Zubereitung', 'ready' => 'Fertig'] as $st => $label)
                    <section>
                        <h2 class="mb-3 flex items-center gap-2 font-semibold {{ $k ? 'text-white/80' : '' }}">
                            <span class="h-2.5 w-2.5 rounded-full {{ ['new' => 'bg-brand', 'preparing' => 'bg-amber-400', 'ready' => 'bg-green-500'][$st] }}"></span>
                            {{ __($label) }} <span class="{{ $muted }}">({{ $columns[$st]->count() }})</span>
                        </h2>
                        <div class="space-y-3">
                            @forelse ($columns[$st] as $o)
                                <div class="{{ $card }} p-4" wire:key="o-{{ $o->id }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-2xl font-extrabold">#{{ $o->number }}</p>
                                            <p class="text-sm {{ $muted }}">
                                                {{ $o->created_at->format('H:i') }}
                                                @if ($o->table_no) · <strong class="{{ $k ? 'text-white' : 'text-ink' }}">{{ __('Tisch') }} {{ $o->table_no }}</strong> @endif
                                                @if ($o->customer_name) · {{ $o->customer_name }} @endif
                                            </p>
                                        </div>
                                        <p class="font-semibold tabular-nums">{{ \App\Models\Order::euro($o->total_cents) }}</p>
                                    </div>
                                    <ul class="mt-3 space-y-1 {{ $k ? 'text-lg' : '' }}">
                                        @foreach ($o->items as $it)
                                            <li><strong>{{ $it['qty'] }}×</strong> {{ $it['name'] }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($o->note)
                                        <p class="mt-2 rounded-xl px-3 py-2 text-sm {{ $k ? 'bg-amber-400/15 text-amber-200' : 'bg-amber-50 text-amber-900' }}">💬 {{ $o->note }}</p>
                                    @endif
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if ($st === 'new')
                                            <button wire:click="setStatus({{ $o->id }}, 'preparing')" class="btn-brand btn-sm">{{ __('Annehmen') }}</button>
                                            <button wire:click="setStatus({{ $o->id }}, 'canceled')" wire:confirm="{{ __('Bestellung #:n stornieren?', ['n' => $o->number]) }}" class="btn btn-sm {{ $k ? 'bg-white/10 text-white' : 'btn-ghost' }}">{{ __('Stornieren') }}</button>
                                        @elseif ($st === 'preparing')
                                            <button wire:click="setStatus({{ $o->id }}, 'ready')" class="btn btn-sm bg-green-600 text-white hover:bg-green-700">{{ __('Fertig') }}</button>
                                        @else
                                            <button wire:click="setStatus({{ $o->id }}, 'done')" class="btn btn-sm {{ $k ? 'bg-white/10 text-white' : 'btn-ghost' }}">{{ __('Abgeholt') }}</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-3xl border border-dashed px-4 py-8 text-center text-sm {{ $k ? 'border-white/15 text-white/40' : 'border-black/10 text-muted' }}">{{ __('Keine') }}</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>

            @if (! $k && $done->count())
                <details class="mt-8">
                    <summary class="cursor-pointer text-sm text-muted">Erledigt heute ({{ $done->count() }})</summary>
                    <div class="mt-3 space-y-1 text-sm">
                        @foreach ($done as $o)
                            <p class="text-muted">#{{ $o->number }} · {{ $o->created_at->format('H:i') }} · {{ __(\App\Models\Order::STATUS[$o->status]) }} · {{ \App\Models\Order::euro($o->total_cents) }}</p>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @endif
</div>
