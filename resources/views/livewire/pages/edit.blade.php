@php
    $t = $page->template;
    $live = 'wire:model.live.debounce.500ms';
@endphp
<div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('pages.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">← Alle Inhalte</a>
        <span class="ml-auto text-xs text-muted">
            <span wire:loading.remove>{{ $savedAt ? 'Gespeichert ' . $savedAt : 'Änderungen werden automatisch gespeichert' }}</span>
            <span wire:loading>Speichert …</span>
        </span>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        {{-- Formular --}}
        <div class="min-w-0 space-y-6">
            <div>
                <input {!! $live !!}="title" class="w-full bg-transparent text-3xl font-bold tracking-tight outline-none placeholder:text-black/25" placeholder="Name der Seite">
                <p class="text-sm text-muted">{{ $page->templateLabel() }}</p>
            </div>

            {{-- Aussehen --}}
            <div class="card p-5">
                <h2 class="font-semibold">Aussehen</h2>
                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                        <button wire:click="$set('theme', 'light')" class="rounded-full px-4 py-1.5 {{ $theme === 'light' ? 'bg-white shadow-sm' : 'text-muted' }}">☀️ Hell</button>
                        <button wire:click="$set('theme', 'dark')" class="rounded-full px-4 py-1.5 {{ $theme === 'dark' ? 'bg-ink text-white shadow-sm' : 'text-muted' }}">🌙 Dunkel</button>
                    </div>
                    <div class="flex items-center gap-2">
                        @foreach (config('signo.accents') as $key => $hex)
                            <button wire:click="$set('accent', '{{ $key }}')" class="h-8 w-8 rounded-full ring-offset-2 transition {{ $accent === $key ? 'ring-2 ring-ink' : 'hover:scale-110' }}" style="background: {{ $hex }}" aria-label="Farbe {{ $key }}"></button>
                        @endforeach
                    </div>
                    @if ($t !== 'media')
                        <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                            <button wire:click="$set('font', 'modern')" class="rounded-full px-4 py-1.5 {{ $font === 'modern' ? 'bg-white shadow-sm' : 'text-muted' }}">Modern</button>
                            <button wire:click="$set('font', 'elegant')" class="rounded-full px-4 py-1.5 font-serif {{ $font === 'elegant' ? 'bg-white shadow-sm' : 'text-muted' }}">Elegant</button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Inhalt --}}
            <div class="card space-y-5 p-5">
                <h2 class="font-semibold">Inhalt</h2>

                @if ($t === 'list')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">Überschrift</label><input {!! $live !!}="data.heading" class="input"></div>
                        <div><label class="label">Untertitel</label><input {!! $live !!}="data.subheading" class="input" placeholder="optional"></div>
                    </div>

                    @foreach ($data['sections'] ?? [] as $s => $sec)
                        <div class="rounded-2xl bg-paper p-4 ring-1 ring-black/5" wire:key="sec-{{ $s }}-{{ count($data['sections']) }}">
                            <div class="flex items-center gap-2">
                                <input {!! $live !!}="data.sections.{{ $s }}.title" class="input font-semibold" placeholder="Gruppe, z. B. Hauptspeisen (optional)">
                                <button wire:click="moveSection({{ $s }}, -1)" class="btn-ghost btn-sm" title="Nach oben">↑</button>
                                <button wire:click="moveSection({{ $s }}, 1)" class="btn-ghost btn-sm" title="Nach unten">↓</button>
                                <button wire:click="removeSection({{ $s }})" wire:confirm="Gruppe mit allen Einträgen löschen?" class="btn-ghost btn-sm text-red-600" title="Gruppe löschen">✕</button>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach ($sec['items'] ?? [] as $i => $it)
                                    <div class="grid grid-cols-[1fr_6.5rem_auto] gap-2 rounded-xl bg-white p-2 ring-1 ring-black/5" wire:key="it-{{ $s }}-{{ $i }}-{{ count($sec['items']) }}">
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.name" class="input py-2 font-medium" placeholder="Bezeichnung">
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.price" class="input py-2 text-right tabular-nums" placeholder="Preis">
                                        <div class="flex items-center">
                                            <div x-data="{ o: false }" class="relative">
                                                <button @click="o = !o" @click.outside="o = false" class="rounded-lg px-2 py-2 text-muted hover:bg-paper" aria-label="Mehr">⋮</button>
                                                <div x-show="o" x-cloak class="absolute right-0 z-20 mt-1 w-36 overflow-hidden rounded-xl bg-white py-1 text-sm shadow-lg ring-1 ring-black/10">
                                                    <button wire:click="moveItem({{ $s }}, {{ $i }}, -1)" class="block w-full px-3 py-1.5 text-left hover:bg-paper">Nach oben</button>
                                                    <button wire:click="moveItem({{ $s }}, {{ $i }}, 1)" class="block w-full px-3 py-1.5 text-left hover:bg-paper">Nach unten</button>
                                                    <button wire:click="removeItem({{ $s }}, {{ $i }})" class="block w-full px-3 py-1.5 text-left text-red-600 hover:bg-red-50">Löschen</button>
                                                </div>
                                            </div>
                                        </div>
                                        <input {!! $live !!}="data.sections.{{ $s }}.items.{{ $i }}.description" class="input col-span-2 py-1.5 text-sm" placeholder="Beschreibung (optional)">
                                    </div>
                                @endforeach
                            </div>
                            <button wire:click="addItem({{ $s }})" class="mt-3 text-sm font-semibold text-ink/70 hover:text-ink">+ Eintrag</button>
                        </div>
                    @endforeach
                    <button wire:click="addSection" class="btn-ghost btn-sm">+ Neue Gruppe</button>
                    <div><label class="label">Fußzeile</label><input {!! $live !!}="data.footer" class="input" placeholder="z. B. Alle Preise in Euro inkl. MwSt."></div>

                @elseif ($t === 'offer')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">Kleine Überschrift</label><input {!! $live !!}="data.kicker" class="input" placeholder="z. B. Nur heute"></div>
                        <div><label class="label">Hauptzeile</label><input {!! $live !!}="data.heading" class="input"></div>
                    </div>
                    <div><label class="label">Text</label><textarea {!! $live !!}="data.text" rows="3" class="input"></textarea></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">Preis</label><input {!! $live !!}="data.price" class="input" placeholder="z. B. 9,90 oder -20 %"></div>
                        <div><label class="label">Alter Preis</label><input {!! $live !!}="data.old_price" class="input" placeholder="optional, wird durchgestrichen"></div>
                    </div>
                    @include('livewire.pages.partials.image', ['key' => 'image', 'label' => 'Bild (optional)'])
                    <div><label class="label">Fußzeile</label><input {!! $live !!}="data.footer" class="input" placeholder="optional"></div>

                @elseif ($t === 'info')
                    <div><label class="label">Überschrift</label><input {!! $live !!}="data.heading" class="input"></div>
                    <div><label class="label">Text</label><textarea {!! $live !!}="data.text" rows="3" class="input" placeholder="optional"></textarea></div>
                    <div>
                        <span class="label">Tabelle (z. B. Öffnungszeiten)</span>
                        <div class="space-y-2">
                            @foreach ($data['rows'] ?? [] as $r => $row)
                                <div class="flex gap-2" wire:key="row-{{ $r }}-{{ count($data['rows']) }}">
                                    <input {!! $live !!}="data.rows.{{ $r }}.label" class="input" placeholder="Montag – Freitag">
                                    <input {!! $live !!}="data.rows.{{ $r }}.value" class="input" placeholder="08:00 – 18:00">
                                    <button wire:click="removeRow({{ $r }})" class="btn-ghost btn-sm text-red-600" aria-label="Zeile löschen">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button wire:click="addRow" class="mt-2 text-sm font-semibold text-ink/70 hover:text-ink">+ Zeile</button>
                    </div>
                    @include('livewire.pages.partials.image', ['key' => 'image', 'label' => 'Bild (optional)'])
                    <div><label class="label">Fußzeile</label><input {!! $live !!}="data.footer" class="input" placeholder="optional"></div>

                @elseif ($t === 'media')
                    @include('livewire.pages.partials.image', ['key' => 'file', 'label' => 'Bild oder Video', 'media' => true])
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                            <button wire:click="$set('data.fit', 'cover')" class="rounded-full px-4 py-1.5 {{ ($data['fit'] ?? 'cover') === 'cover' ? 'bg-white shadow-sm' : 'text-muted' }}">Bildschirm füllen</button>
                            <button wire:click="$set('data.fit', 'contain')" class="rounded-full px-4 py-1.5 {{ ($data['fit'] ?? '') === 'contain' ? 'bg-white shadow-sm' : 'text-muted' }}">Ganz zeigen</button>
                        </div>
                    </div>
                    <div><label class="label">Beschriftung</label><input {!! $live !!}="data.caption" class="input" placeholder="optional"></div>
                @endif
            </div>

            <div class="card flex items-center justify-between gap-4 p-5">
                <div>
                    <p class="font-semibold">Per QR-Code am Handy abrufbar</p>
                    <p class="text-sm text-muted">Gäste sehen diese Seite, wenn sie den QR-Code scannen.</p>
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
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('pages.preview', $page) }}" target="_blank" class="btn-ghost btn-sm">Vollbild</a>
                <a href="{{ route('pages.preview', ['page' => $page, 'portrait' => 1]) }}" target="_blank" class="btn-ghost btn-sm">Hochformat</a>
                @if ($isPublic)
                    <a href="{{ $page->publicUrl() }}" target="_blank" class="btn-ghost btn-sm">Handy-Ansicht</a>
                @endif
            </div>
            <p class="hint mt-3">Die Schrift passt sich automatisch an, damit alles auf den Bildschirm passt.</p>
        </div>
    </div>
</div>
