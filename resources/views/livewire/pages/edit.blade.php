@php
    $t = $page->template;
    $live = 'wire:model.live.debounce.500ms';
@endphp
<div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('pages.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">{{ __('← Alle Inhalte') }}</a>
        <span class="ml-auto text-xs text-muted">
            <span wire:loading.remove>{{ $savedAt ? __('Gespeichert') . ' ' . $savedAt : __('Änderungen werden automatisch gespeichert') }}</span>
            <span wire:loading>{{ __('Speichert …') }}</span>
        </span>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        {{-- Formular --}}
        <div class="min-w-0 space-y-6">
            <div>
                <input {!! $live !!}="title" class="w-full bg-transparent text-3xl font-bold tracking-tight outline-none placeholder:text-black/25" placeholder="{{ __('Name der Seite') }}">
                <p class="text-sm text-muted">{{ __($page->templateLabel()) }}</p>
            </div>

            {{-- Aussehen --}}
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('Aussehen') }}</h2>
                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                        <button wire:click="$set('theme', 'light')" class="rounded-full px-4 py-1.5 {{ $theme === 'light' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('☀️ Hell') }}</button>
                        <button wire:click="$set('theme', 'dark')" class="rounded-full px-4 py-1.5 {{ $theme === 'dark' ? 'bg-ink text-white shadow-sm' : 'text-muted' }}">{{ __('🌙 Dunkel') }}</button>
                    </div>
                    <div class="flex items-center gap-2">
                        @foreach (config('signo.accents') as $key => $hex)
                            <button wire:click="$set('accent', '{{ $key }}')" class="h-8 w-8 rounded-full ring-offset-2 transition {{ $accent === $key ? 'ring-2 ring-ink' : 'hover:scale-110' }}" style="background: {{ $hex }}" aria-label="{{ __('Farbe') }} {{ $key }}"></button>
                        @endforeach
                        @if ($org->brandAccent())
                            <button wire:click="$set('accent', 'brand')" class="flex h-8 items-center gap-1 rounded-full px-3 text-xs font-semibold text-white ring-offset-2 transition {{ $accent === 'brand' ? 'ring-2 ring-ink' : 'hover:scale-105' }}" style="background: {{ $org->brandAccent() }}">{{ __('Marke') }}</button>
                        @endif
                    </div>
                </div>
                @if ($t !== 'media' && $t !== 'orders')
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (config('signo.fonts') as $key => $label)
                            <button wire:click="$set('font', '{{ $key }}')" class="rounded-full px-4 py-1.5 text-sm font-medium ring-1 transition {{ $font === $key ? 'bg-ink text-white ring-ink' : 'bg-white ring-black/10 hover:ring-black/30' }}">{{ __($label) }}</button>
                        @endforeach
                    </div>
                    @if (! $org->brandAccent())
                        <p class="hint mt-3">{!! __('Eigene Farbe und Logo: unter :link einmal das Logo hochladen.', ['link' => '<a href="' . route('account') . '#marke" class="underline">' . e(__('Konto → Marke')) . '</a>']) !!}</p>
                    @endif
                @endif
            </div>


            {{-- Ändern per Sprache --}}
            @if ($t !== 'media' && $aiReady)
                <div class="card p-5" x-data="signoVoice()">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ __('🎙️ Schnell ändern') }}</h2>
                            <p class="text-sm text-muted">{{ __('Einfach sagen oder schreiben, z. B. „Schnitzel kostet jetzt 19,90, Gulasch streichen“.') }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="button" x-show="supported" @click="toggle()" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg transition"
                                :class="listening ? 'bg-brand text-white animate-pulse' : 'bg-sand hover:bg-ink hover:text-white'" :aria-label="listening ? @js(__('Aufnahme stoppen')) : @js(__('Sprechen'))">🎙️</button>
                        <input wire:model="voiceText" x-ref="voiceInput" wire:keydown.enter="voiceRun" class="input" placeholder="{{ __('Was soll sich ändern?') }}">
                        <button wire:click="voiceRun" class="btn-primary shrink-0" wire:loading.attr="disabled" wire:target="voiceRun">
                            <span wire:loading.remove wire:target="voiceRun">{{ __('Los') }}</span>
                            <span wire:loading wire:target="voiceRun">…</span>
                        </button>
                    </div>
                    <p x-show="listening" class="hint">{{ __('Ich höre zu … zum Beenden nochmal tippen.') }}</p>
                    @if ($voiceError) <p class="error mt-3">{{ $voiceError }}</p> @endif
                    @if ($voicePreview)
                        <div class="mt-4 rounded-2xl bg-paper p-4 ring-1 ring-black/5">
                            <p class="text-sm font-semibold">{{ __('Diese Änderungen werden übernommen:') }}</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($voicePreview['changes'] as $c)
                                    <li class="flex gap-2"><span class="{{ ['add' => 'text-green-700', 'remove' => 'text-red-600', 'change' => 'text-ink'][$c['type']] ?? '' }}">{{ ['add' => '+', 'remove' => '−', 'change' => '✎'][$c['type']] ?? '•' }}</span><span>{{ $c['text'] }}</span></li>
                                @endforeach
                            </ul>
                            <div class="mt-4 flex gap-2">
                                <button wire:click="voiceApply" class="btn-brand btn-sm">{{ __('Übernehmen') }}</button>
                                <button wire:click="voiceDiscard" class="btn-ghost btn-sm">{{ __('Verwerfen') }}</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Inhalt --}}
            <div class="card space-y-5 p-5">
                <h2 class="font-semibold">{{ __('Inhalt') }}</h2>

                @if (in_array($t, ['offer', 'info']) && $aiReady)
                    <div class="rounded-2xl bg-paper p-4 ring-1 ring-black/5">
                        <p class="text-sm font-semibold">{{ __('✨ Texte vorschlagen lassen') }}</p>
                        <form wire:submit="suggest" class="mt-2 flex gap-2">
                            <input wire:model="idea" class="input" placeholder="{{ __('Stichworte, z. B. Kürbissuppe, Herbst, hausgemacht') }}">
                            <button class="btn-primary btn-sm shrink-0" wire:loading.attr="disabled" wire:target="suggest">
                                <span wire:loading.remove wire:target="suggest">{{ __('Vorschlagen') }}</span><span wire:loading wire:target="suggest">…</span>
                            </button>
                        </form>
                        @if ($ideaError) <p class="error mt-2">{{ $ideaError }}</p> @endif
                        @if ($ideas)
                            <div class="mt-3 grid gap-2">
                                @foreach ($ideas as $i => $idea)
                                    <button wire:click="useIdea({{ $i }})" class="rounded-xl bg-white p-3 text-left ring-1 ring-black/10 transition hover:ring-ink">
                                        @if ($t === 'offer' && $idea['kicker'])<span class="chip bg-brand text-white">{{ $idea['kicker'] }}</span>@endif
                                        <span class="mt-1 block font-semibold">{{ $idea['heading'] }}</span>
                                        <span class="block text-sm text-muted">{{ $idea['text'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($t === 'list')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Überschrift') }}</label><input {!! $live !!}="data.heading" class="input"></div>
                        <div><label class="label">{{ __('Untertitel') }}</label><input {!! $live !!}="data.subheading" class="input" placeholder="{{ __('optional') }}"></div>
                    </div>

                    @foreach ($data['sections'] ?? [] as $s => $sec)
                        <div class="rounded-2xl bg-paper p-4 ring-1 ring-black/5" wire:key="sec-{{ $s }}-{{ count($data['sections']) }}">
                            <div class="flex items-center gap-2">
                                <input {!! $live !!}="data.sections.{{ $s }}.title" class="input font-semibold" placeholder="{{ __('Gruppe, z. B. Hauptspeisen (optional)') }}">
                                <button wire:click="moveSection({{ $s }}, -1)" class="btn-ghost btn-sm" title="{{ __('Nach oben') }}">↑</button>
                                <button wire:click="moveSection({{ $s }}, 1)" class="btn-ghost btn-sm" title="{{ __('Nach unten') }}">↓</button>
                                <button wire:click="removeSection({{ $s }})" wire:confirm="{{ __('Gruppe mit allen Einträgen löschen?') }}" class="btn-ghost btn-sm text-red-600" title="{{ __('Gruppe löschen') }}">✕</button>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($sec['items'] ?? [] as $i => $it)
                                    <div class="grid grid-cols-[1fr_6.5rem_auto] gap-2 rounded-xl bg-white p-2 ring-1 ring-black/5" wire:key="it-{{ $s }}-{{ $i }}-{{ count($sec['items']) }}">
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.name" class="input py-2 font-medium" placeholder="{{ __('Bezeichnung') }}">
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.price" class="input py-2 text-right tabular-nums" placeholder="{{ __('Preis') }}">
                                        <div class="flex items-center">
                                            <div x-data="{ o: false }" class="relative">
                                                <button @click="o = !o" @click.outside="o = false" class="rounded-lg px-2 py-2 text-muted hover:bg-paper" aria-label="{{ __('Mehr') }}">⋮</button>
                                                <div x-show="o" x-cloak class="absolute right-0 z-20 mt-1 w-36 overflow-hidden rounded-xl bg-white py-1 text-sm shadow-lg ring-1 ring-black/10">
                                                    <button wire:click="moveItem({{ $s }}, {{ $i }}, -1)" class="block w-full px-3 py-1.5 text-left hover:bg-paper">{{ __('Nach oben') }}</button>
                                                    <button wire:click="moveItem({{ $s }}, {{ $i }}, 1)" class="block w-full px-3 py-1.5 text-left hover:bg-paper">{{ __('Nach unten') }}</button>
                                                    <button wire:click="removeItem({{ $s }}, {{ $i }})" class="block w-full px-3 py-1.5 text-left text-red-600 hover:bg-red-50">{{ __('Löschen') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.description" class="input col-span-2 py-1.5 text-sm" placeholder="{{ __('Beschreibung (optional)') }}">
                                        @include('livewire.pages.partials.item-extras', ['path' => "sections.$s.items.$i", 'it' => $it, 'happyOn' => $data['happy']['enabled'] ?? false])
                                    </div>
                                @endforeach
                            </div>
                            <button wire:click="addItem({{ $s }})" class="mt-3 text-sm font-semibold text-ink/70 hover:text-ink">{{ __('+ Eintrag') }}</button>
                        </div>
                    @endforeach
                    <button wire:click="addSection" class="btn-ghost btn-sm">{{ __('+ Neue Gruppe') }}</button>
                    <div><label class="label">{{ __('Fußzeile') }}</label><input {!! $live !!}="data.footer" class="input" placeholder="{{ __('z. B. Alle Preise in Euro inkl. MwSt.') }}"></div>
                    <div><label class="label">{{ __('Laufschrift (optional)') }}</label><input {!! $live !!}="data.ticker" class="input" placeholder="{{ __('z. B. Heute Live-Musik ab 19 Uhr · Happy Hour 17–18 Uhr') }}"><p class="hint">{{ __('Läuft am Bildschirm unten durchs Bild.') }}</p></div>
                    <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" wire:model.live="data.legend" class="h-4 w-4 accent-ink"> {{ __('Allergen-Legende unter der Liste zeigen') }}</label>

                @elseif ($t === 'week')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Überschrift') }}</label><input {!! $live !!}="data.heading" class="input"></div>
                        <div><label class="label">{{ __('Untertitel') }}</label><input {!! $live !!}="data.subheading" class="input" placeholder="{{ __('z. B. 11:30 – 14:00 Uhr') }}"></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Menüpreis (optional)') }}</label><input {!! $live !!}="data.price" class="input" placeholder="z. B. 10,90"><p class="hint">{{ __('Ein Preis für das ganze Menü. Einzelpreise gehen zusätzlich bei jedem Gericht.') }}</p></div>
                        <div>
                            <span class="label">{{ __('Am Bildschirm zeigen') }}</span>
                            <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                                <button wire:click="$set('data.view', 'today')" class="rounded-full px-4 py-1.5 {{ ($data['view'] ?? 'today') === 'today' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Nur heute') }}</button>
                                <button wire:click="$set('data.view', 'week')" class="rounded-full px-4 py-1.5 {{ ($data['view'] ?? '') === 'week' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Ganze Woche') }}</button>
                            </div>
                        </div>
                    </div>
                    @php $todayIdx = now()->timezone(config('app.timezone'))->dayOfWeekIso - 1; @endphp
                    @foreach (range(0, 6) as $dn)
                        @php $dayItems = $data['days'][$dn]['items'] ?? []; @endphp
                        <div class="rounded-2xl bg-paper p-4 ring-1 {{ $dn === $todayIdx ? 'ring-ink' : 'ring-black/5' }}" wire:key="day-{{ $dn }}-{{ count($dayItems) }}">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold">{{ \App\Support\Ui::weekday($dn, app()->getLocale()) }}</p>
                                @if ($dn === $todayIdx)<span class="chip bg-ink text-white">{{ __('Heute') }}</span>@endif
                                @if ($dn > 0 && ! count($dayItems) && count($data['days'][$dn - 1]['items'] ?? []))
                                    <button wire:click="copyDay({{ $dn - 1 }}, {{ $dn }})" class="ml-auto text-xs font-semibold text-ink/70 hover:text-ink">{{ __('Vom Vortag übernehmen') }}</button>
                                @endif
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($dayItems as $i => $it)
                                    <div class="grid grid-cols-[1fr_6.5rem_auto] gap-2 rounded-xl bg-white p-2 ring-1 ring-black/5" wire:key="di-{{ $dn }}-{{ $i }}">
                                        <input {!! $live !!}="data.days.{{ $dn }}.items.{{ $i }}.name" class="input py-2 font-medium" placeholder="{{ __('Gericht') }}">
                                        <input {!! $live !!}="data.days.{{ $dn }}.items.{{ $i }}.price" class="input py-2 text-right tabular-nums" placeholder="{{ __('Preis') }}">
                                        <button wire:click="removeDayItem({{ $dn }}, {{ $i }})" class="rounded-lg px-2 text-red-600 hover:bg-red-50" aria-label="{{ __('Löschen') }}">✕</button>
                                        <input {!! $live !!}="data.days.{{ $dn }}.items.{{ $i }}.description" class="input col-span-2 py-1.5 text-sm" placeholder="{{ __('Beschreibung (optional)') }}">
                                        @include('livewire.pages.partials.item-extras', ['path' => "days.$dn.items.$i", 'it' => $it, 'happyOn' => false])
                                    </div>
                                @endforeach
                            </div>
                            <button wire:click="addDayItem({{ $dn }})" class="mt-3 text-sm font-semibold text-ink/70 hover:text-ink">{{ __('+ Gericht') }}</button>
                        </div>
                    @endforeach
                    <p class="hint">{{ __('Tage ohne Gericht (z. B. Ruhetag) werden am Bildschirm ausgelassen.') }}</p>
                    <div><label class="label">{{ __('Fußzeile') }}</label><input {!! $live !!}="data.footer" class="input" placeholder="{{ __('z. B. Jedes Menü mit Suppe oder Salat') }}"></div>
                    <div><label class="label">{{ __('Laufschrift (optional)') }}</label><input {!! $live !!}="data.ticker" class="input"></div>
                    <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" wire:model.live="data.legend" class="h-4 w-4 accent-ink"> {{ __('Allergen-Legende unter der Liste zeigen') }}</label>

                @elseif ($t === 'offer')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Kleine Überschrift') }}</label><input {!! $live !!}="data.kicker" class="input" placeholder="{{ __('z. B. Nur heute') }}"></div>
                        <div><label class="label">{{ __('Hauptzeile') }}</label><input {!! $live !!}="data.heading" class="input"></div>
                    </div>
                    <div><label class="label">{{ __('Text') }}</label><textarea {!! $live !!}="data.text" rows="3" class="input"></textarea></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Preis') }}</label><input {!! $live !!}="data.price" class="input" placeholder="{{ __('z. B. 9,90 oder -20 %') }}"></div>
                        <div><label class="label">{{ __('Alter Preis') }}</label><input {!! $live !!}="data.old_price" class="input" placeholder="{{ __('optional, wird durchgestrichen') }}"></div>
                    </div>
                    @include('livewire.pages.partials.image', ['key' => 'image', 'label' => 'Bild (optional)'])
                    <div><label class="label">{{ __('Fußzeile') }}</label><input {!! $live !!}="data.footer" class="input" placeholder="{{ __('optional') }}"></div>
                    <div><label class="label">{{ __('Laufschrift (optional)') }}</label><input {!! $live !!}="data.ticker" class="input" placeholder="{{ __('z. B. Heute Live-Musik ab 19 Uhr · Happy Hour 17–18 Uhr') }}"><p class="hint">{{ __('Läuft am Bildschirm unten durchs Bild.') }}</p></div>

                @elseif ($t === 'info')
                    <div><label class="label">{{ __('Überschrift') }}</label><input {!! $live !!}="data.heading" class="input"></div>
                    <div><label class="label">{{ __('Text') }}</label><textarea {!! $live !!}="data.text" rows="3" class="input" placeholder="{{ __('optional') }}"></textarea></div>
                    <div>
                        <span class="label">{{ __('Tabelle (z. B. Öffnungszeiten)') }}</span>
                        <div class="space-y-2">
                            @foreach ($data['rows'] ?? [] as $r => $row)
                                <div class="flex gap-2" wire:key="row-{{ $r }}-{{ count($data['rows']) }}">
                                    <input {!! $live !!}="data.rows.{{ $r }}.label" class="input" placeholder="{{ __('Montag – Freitag') }}">
                                    <input {!! $live !!}="data.rows.{{ $r }}.value" class="input" placeholder="08:00 – 18:00">
                                    <button wire:click="removeRow({{ $r }})" class="btn-ghost btn-sm text-red-600" aria-label="{{ __('Zeile löschen') }}">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button wire:click="addRow" class="mt-2 text-sm font-semibold text-ink/70 hover:text-ink">{{ __('+ Zeile') }}</button>
                    </div>
                    @include('livewire.pages.partials.image', ['key' => 'image', 'label' => 'Bild (optional)'])
                    <div><label class="label">{{ __('Fußzeile') }}</label><input {!! $live !!}="data.footer" class="input" placeholder="{{ __('optional') }}"></div>
                    <div><label class="label">{{ __('Laufschrift (optional)') }}</label><input {!! $live !!}="data.ticker" class="input" placeholder="{{ __('z. B. Heute Live-Musik ab 19 Uhr · Happy Hour 17–18 Uhr') }}"><p class="hint">{{ __('Läuft am Bildschirm unten durchs Bild.') }}</p></div>

                @elseif ($t === 'clock')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Überschrift') }}</label><input {!! $live !!}="data.heading" class="input" placeholder="{{ __('optional, z. B. Willkommen') }}"></div>
                        <div><label class="label">{{ __('Ort für das Wetter') }}</label><input {!! $live !!}="data.city" class="input" placeholder="{{ __('z. B. Wien') }}"></div>
                    </div>
                    <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" wire:model.live="data.weather" class="h-4 w-4 accent-ink"> {{ __('Aktuelles Wetter anzeigen') }}</label>
                    <div><label class="label">{{ __('Text darunter') }}</label><input {!! $live !!}="data.text" class="input" placeholder="{{ __('optional') }}"></div>
                @elseif ($t === 'countdown')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('Überschrift') }}</label><input {!! $live !!}="data.heading" class="input"></div>
                        <div><label class="label">{{ __('Zeitpunkt') }}</label><input type="datetime-local" wire:model.live="data.target" class="input"></div>
                    </div>
                    <div><label class="label">{{ __('Text') }}</label><input {!! $live !!}="data.text" class="input" placeholder="{{ __('optional') }}"></div>
                    <div><label class="label">{{ __('Text, wenn es so weit ist') }}</label><input {!! $live !!}="data.done_text" class="input" placeholder="{{ __('z. B. Jetzt geöffnet!') }}"></div>
                @elseif ($t === 'orders')
                    <p class="text-sm text-muted">{{ __('Diese Seite zeigt automatisch die Bestellnummern „In Zubereitung“ und „Bitte abholen“. Sie wird von der Küche gesteuert – hier gibt es nichts einzutragen.') }}</p>
                @elseif ($t === 'media')
                    @include('livewire.pages.partials.image', ['key' => 'file', 'label' => 'Bild oder Video', 'media' => true])
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                            <button wire:click="$set('data.fit', 'cover')" class="rounded-full px-4 py-1.5 {{ ($data['fit'] ?? 'cover') === 'cover' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Bildschirm füllen') }}</button>
                            <button wire:click="$set('data.fit', 'contain')" class="rounded-full px-4 py-1.5 {{ ($data['fit'] ?? '') === 'contain' ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Ganz zeigen') }}</button>
                        </div>
                    </div>
                    <div><label class="label">{{ __('Beschriftung') }}</label><input {!! $live !!}="data.caption" class="input" placeholder="{{ __('optional') }}"></div>
                @endif
            </div>



            {{-- Happy Hour --}}
            @if ($t === 'list')
                @php $hh = $data['happy'] ?? ['enabled' => false]; @endphp
                <div class="card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-semibold">{{ __('🍹 Happy Hour & Zeitpreise') }}</h2>
                            <p class="text-sm text-muted">{{ __('Zu festen Zeiten gelten Aktionspreise – danach springen sie von selbst zurück.') }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="data.happy.enabled" class="peer sr-only">
                            <span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-ink"></span>
                            <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </label>
                    </div>
                    @if ($hh['enabled'] ?? false)
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><label class="label">{{ __('Bezeichnung') }}</label><input {!! $live !!}="data.happy.label" class="input" placeholder="{{ __('Happy Hour') }}"></div>
                            <div>
                                <span class="label">{{ __('Uhrzeit') }}</span>
                                <div class="flex items-center gap-2 text-sm">
                                    <input type="time" wire:model.live.debounce.700ms="data.happy.start" class="input py-1.5"> <span>{{ __('bis') }}</span>
                                    <input type="time" wire:model.live.debounce.700ms="data.happy.end" class="input py-1.5">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $n => $lbl)
                                <label class="cursor-pointer">
                                    <input type="checkbox" wire:model.live="data.happy.days" value="{{ $n }}" class="peer sr-only">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium ring-1 ring-black/10 peer-checked:bg-ink peer-checked:text-white">{{ __($lbl) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="hint mt-3">
                            @if (empty($hh['start']) || empty($hh['end']))
                                Bitte Beginn und Ende eintragen. Dann oben bei den Einträgen das gelbe Feld „Aktionspreis“ ausfüllen.
                            @elseif ($preview->happyActive())
                                <span class="font-semibold text-green-700">{{ __('Läuft gerade.') }}</span> {{ __('Einträge mit Aktionspreis zeigen jetzt den neuen Preis, der alte ist durchgestrichen.') }}
                            @else
                                Oben bei den Einträgen das gelbe Feld „Aktionspreis“ ausfüllen. Einträge ohne Aktionspreis bleiben unverändert.
                            @endif
                        </p>
                    @endif
                </div>
            @endif

            {{-- Zeitplan --}}
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('🕒 Wann anzeigen?') }}</h2>
                <div class="mt-4 inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                    <button wire:click="$set('schedule.enabled', false)" class="rounded-full px-4 py-1.5 {{ ! $schedule['enabled'] ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Immer') }}</button>
                    <button wire:click="$set('schedule.enabled', true)" class="rounded-full px-4 py-1.5 {{ $schedule['enabled'] ? 'bg-white shadow-sm' : 'text-muted' }}">{{ __('Nach Zeitplan') }}</button>
                </div>
                @if ($schedule['enabled'])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $n => $lbl)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model.live="schedule.days" value="{{ $n }}" class="peer sr-only">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium ring-1 ring-black/10 peer-checked:bg-ink peer-checked:text-white">{{ __($lbl) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="label">{{ __('Uhrzeit (optional)') }}</span>
                            <div class="flex items-center gap-2 text-sm">
                                <input type="time" wire:model.live.debounce.700ms="schedule.start" class="input py-1.5"> <span>{{ __('bis') }}</span>
                                <input type="time" wire:model.live.debounce.700ms="schedule.end" class="input py-1.5">
                            </div>
                        </div>
                        <div>
                            <span class="label">{{ __('Zeitraum (optional)') }}</span>
                            <div class="flex items-center gap-2 text-sm">
                                <input type="date" wire:model.live="schedule.from" class="input py-1.5"> <span>{{ __('bis') }}</span>
                                <input type="date" wire:model.live="schedule.until" class="input py-1.5">
                            </div>
                        </div>
                    </div>
                    <p class="hint mt-3">{{ __('Beispiel: Frühstückskarte Mo–Fr 06:00–11:00, Aktion vom 1. bis 24. Dezember. Außerhalb läuft die Seite nicht auf den Bildschirmen.') }}</p>
                @endif
            </div>

            {{-- Sprachen --}}
            @if ($t !== 'media')
                <div class="card p-5">
                    <h2 class="font-semibold">{{ __('🌍 Weitere Sprachen') }}</h2>
                    @if (! $aiReady)
                        <p class="mt-1 text-sm text-muted">{{ __('Automatische Übersetzungen sind verfügbar, sobald die KI eingerichtet ist.') }}</p>
                    @else
                        <p class="mt-1 text-sm text-muted">{{ __('Gäste sehen die Handy-Seite in ihrer Sprache. Preise bleiben gleich, übersetzt wird automatisch.') }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (\App\Support\Translations::LANGUAGES as $code => [$name, $short])
                                <button wire:click="toggleLanguage('{{ $code }}')" wire:loading.attr="disabled" wire:target="toggleLanguage,translate"
                                        class="rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition {{ in_array($code, $languages) ? 'bg-ink text-white ring-ink' : 'bg-white ring-black/10 hover:ring-black/30' }}">{{ $name }}</button>
                            @endforeach
                        </div>
                        <div class="mt-3 text-sm">
                            <span wire:loading wire:target="toggleLanguage,translate" class="text-muted">{{ __('Übersetze … (dauert ein paar Sekunden)') }}</span>
                            <span wire:loading.remove wire:target="toggleLanguage,translate">
                                @if ($langMessage) <span class="{{ $langError ? 'text-red-600' : 'text-green-700' }}">{{ $langMessage }}</span> @endif
                                @if ($languages && $missing > 0)
                                    <span class="text-amber-700">{{ trans_choice(':n geänderter Text ist noch nicht übersetzt.|:n geänderte Texte sind noch nicht übersetzt.', $missing, ['n' => $missing]) }}</span>
                                    <button wire:click="translate" class="ml-1 font-semibold underline">{{ __('Jetzt übersetzen') }}</button>
                                @endif
                            </span>
                        </div>
                        @if ($languages)
                            <label class="mt-4 flex items-center gap-2.5 text-sm">
                                <input type="checkbox" wire:model.live="tvLanguages" class="h-4 w-4 accent-ink">
                                {{ __('Am Bildschirm alle Sprachen nacheinander zeigen') }}
                            </label>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Bestellen --}}
            @if ($t === 'list')
                <div class="card flex items-center justify-between gap-4 p-5">
                    <div>
                        <p class="font-semibold">{{ __('🛎️ Per Handy bestellbar') }}</p>
                        @if ($org->ordering_enabled)
                            <p class="text-sm text-muted">{{ __('Gäste legen Einträge mit Preis in den Warenkorb und bestellen über den QR-Code.') }}</p>
                        @else
                            <p class="text-sm text-muted">{!! __('Zuerst unter :link einschalten.', ['link' => '<a href="' . route('orders') . '" class="underline">' . e(__('Bestellungen')) . '</a>']) !!}</p>
                        @endif
                    </div>
                    @if ($org->ordering_enabled)
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.live="orderable" class="peer sr-only">
                            <span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-ink"></span>
                            <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </label>
                    @endif
                </div>
            @endif

            <div class="card flex items-center justify-between gap-4 p-5">
                <div>
                    <p class="font-semibold">{{ __('Per QR-Code am Handy abrufbar') }}</p>
                    <p class="text-sm text-muted">{{ __('Gäste sehen diese Seite, wenn sie den QR-Code scannen.') }}</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" wire:model.live="isPublic" class="peer sr-only">
                    <span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-ink"></span>
                    <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </label>
            </div>
        </div>

        {{-- Vorschau --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <div class="sg-stage aspect-video overflow-hidden rounded-3xl shadow-xl ring-1 ring-black/10">
                @include('display.page', ['page' => $preview, 'mode' => 'tv'])
            </div>
            @if ($page->availableLanguages())
                <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                    <button wire:click="$set('previewLang', 'de')" class="rounded-full px-2.5 py-1 font-semibold {{ $previewLang === 'de' ? 'bg-ink text-white' : 'bg-white ring-1 ring-black/10' }}">DE</button>
                    @foreach ($page->availableLanguages() as $l)
                        <button wire:click="$set('previewLang', '{{ $l }}')" class="rounded-full px-2.5 py-1 font-semibold {{ $previewLang === $l ? 'bg-ink text-white' : 'bg-white ring-1 ring-black/10' }}">{{ \App\Support\Translations::LANGUAGES[$l][1] }}</button>
                    @endforeach
                </div>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('pages.preview', $page) }}" target="_blank" class="btn-ghost btn-sm">{{ __('Vollbild') }}</a>
                <a href="{{ route('pages.preview', ['page' => $page, 'portrait' => 1]) }}" target="_blank" class="btn-ghost btn-sm">{{ __('Hochformat') }}</a>
                @if ($isPublic)
                    <a href="{{ $page->publicUrl() }}" target="_blank" class="btn-ghost btn-sm">{{ __('Handy-Ansicht') }}</a>
                @endif
                @if (! in_array($t, ['media', 'orders']))
                    <a href="{{ route('pages.export', $page) }}" wire:navigate class="btn-ghost btn-sm">{{ __('Export: Druck & Social Media') }}</a>
                @endif
            </div>
            <p class="hint mt-3">{{ __('Die Schrift passt sich automatisch an, damit alles auf den Bildschirm passt.') }}</p>
        </div>
    </div>
</div>
