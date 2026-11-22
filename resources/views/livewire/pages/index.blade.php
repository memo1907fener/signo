<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Inhalte</h1>
            <p class="mt-1 text-muted">Ihre Seiten für Bildschirm, QR-Code und Handy.</p>
        </div>
        <button wire:click="$set('creating', true)" class="btn-brand">+ Neue Seite</button>
    </div>

    @if ($creating)
        <div class="card mt-6 p-5 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div class="inline-flex rounded-full bg-sand p-1 text-sm font-medium">
                    <button wire:click="$set('mode', 'ai')" class="rounded-full px-4 py-1.5 {{ $mode === 'ai' ? 'bg-white shadow-sm' : 'text-muted' }}">✨ Mit KI aus Foto/PDF</button>
                    <button wire:click="$set('mode', 'template')" class="rounded-full px-4 py-1.5 {{ $mode === 'template' ? 'bg-white shadow-sm' : 'text-muted' }}">Leere Vorlage</button>
                </div>
                <button wire:click="$set('creating', false)" class="text-2xl leading-none text-muted hover:text-ink" aria-label="Schließen">×</button>
            </div>

            @if ($mode === 'ai')
                @if (! $aiReady)
                    <div class="mt-6 rounded-2xl bg-sand/60 p-5 text-sm">
                        <strong>Der KI-Import wird gerade eingerichtet.</strong>
                        <p class="mt-1 text-muted">Bis dahin können Sie mit einer leeren Vorlage starten – das dauert nur eine Minute.</p>
                    </div>
                @else
                    <form wire:submit="import" class="mt-6 grid gap-5 md:grid-cols-2">
                        <label class="flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-black/15 bg-paper p-6 text-center transition hover:border-ink"
                               x-data="{ over: false }" x-on:dragover.prevent="over = true" x-on:dragleave="over = false" x-on:drop="over = false" :class="over && 'border-ink'">
                            <input type="file" wire:model="upload" accept="image/jpeg,image/png,image/webp,application/pdf" class="sr-only">
                            <span class="text-4xl">📷</span>
                            @if ($upload)
                                <span class="mt-3 font-semibold">{{ $upload->getClientOriginalName() }}</span>
                                <span class="text-xs text-muted">Andere Datei wählen</span>
                            @else
                                <span class="mt-3 font-semibold">Foto oder PDF hochladen</span>
                                <span class="mt-1 text-sm text-muted">z. B. Ihre Speisekarte, Preisliste oder ein Plakat</span>
                            @endif
                            <span wire:loading wire:target="upload" class="mt-2 text-xs text-muted">Wird hochgeladen …</span>
                        </label>
                        <div class="flex flex-col">
                            <label class="label" for="aitext">… oder Text einfügen</label>
                            <textarea id="aitext" wire:model="text" rows="6" class="input flex-1" placeholder="z. B. Mittagsmenü Montag: Gulaschsuppe 4,90 · Schnitzel mit Salat 12,90 …"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            @error('upload') <p class="error mb-3">{{ $message }}</p> @enderror
                            @if ($aiError) <p class="error mb-3">{{ $aiError }}</p> @endif
                            <button class="btn-primary w-full py-3 text-base sm:w-auto" wire:loading.attr="disabled" wire:target="import,upload">
                                <span wire:loading.remove wire:target="import">Seite erstellen</span>
                                <span wire:loading wire:target="import">Die KI liest Ihre Vorlage … (bis zu 1 Minute)</span>
                            </button>
                            <p class="hint mt-2">Sie können danach alles prüfen und ändern.</p>
                        </div>
                    </form>
                @endif
            @else
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (collect(config('signo.templates'))->reject(fn ($t) => $t['hidden'] ?? false) as $key => $t)
                        <button wire:click="create('{{ $key }}')" class="rounded-3xl bg-paper p-5 text-left ring-1 ring-black/5 transition hover:ring-ink">
                            <span class="block text-2xl">{{ ['list' => '📋', 'offer' => '🏷️', 'info' => 'ℹ️', 'media' => '🖼️'][$key] }}</span>
                            <span class="mt-3 block font-semibold">{{ $t['label'] }}</span>
                            <span class="mt-0.5 block text-sm text-muted">{{ $t['hint'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($pages->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <p class="text-lg font-semibold">Noch keine Seiten</p>
            <p class="mt-1 text-muted">Laden Sie ein Foto Ihrer Speisekarte oder Preisliste hoch – den Rest macht Signo.</p>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($pages as $p)
                <div class="card group overflow-hidden" wire:key="page-{{ $p->id }}">
                    <a href="{{ route('pages.edit', $p) }}" wire:navigate class="block p-2">
                        <div class="sg-stage aspect-video rounded-2xl ring-1 ring-black/5">
                            @include('display.page', ['page' => $p, 'mode' => 'tv'])
                        </div>
                    </a>
                    <div class="flex items-center gap-2 px-5 pb-4 pt-2">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('pages.edit', $p) }}" wire:navigate class="block truncate font-semibold hover:underline">{{ $p->title }}</a>
                            <p class="text-xs text-muted">{{ $p->templateLabel() }} · {{ $p->theme === 'dark' ? 'Dunkel' : 'Hell' }}</p>
                        </div>
                        <button wire:click="$set('qrPage', {{ $p->id }})" class="btn-ghost btn-sm" title="QR-Code">QR</button>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.outside="open = false" class="btn-ghost btn-sm" aria-label="Mehr">•••</button>
                            <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-2xl bg-white py-1 text-sm shadow-xl ring-1 ring-black/10">
                                <a href="{{ route('pages.edit', $p) }}" wire:navigate class="block px-4 py-2 hover:bg-paper">Bearbeiten</a>
                                <a href="{{ $p->publicUrl() }}" target="_blank" class="block px-4 py-2 hover:bg-paper">Handy-Ansicht</a>
                                <button wire:click="move({{ $p->id }}, -1)" class="block w-full px-4 py-2 text-left hover:bg-paper">Nach vorne</button>
                                <button wire:click="move({{ $p->id }}, 1)" class="block w-full px-4 py-2 text-left hover:bg-paper">Nach hinten</button>
                                <button wire:click="duplicate({{ $p->id }})" class="block w-full px-4 py-2 text-left hover:bg-paper">Duplizieren</button>
                                <button wire:click="delete({{ $p->id }})" wire:confirm="Seite „{{ $p->title }}“ wirklich löschen?" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">Löschen</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($qr)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="$set('qrPage', null)">
            <div class="card w-full max-w-sm p-6 text-center">
                <h2 class="text-lg font-bold">QR-Code: {{ $qr->title }}</h2>
                <p class="mt-1 text-sm text-muted">Gäste scannen und sehen die Seite am Handy – immer aktuell.</p>
                <canvas data-qr="{{ $qr->publicUrl() }}" data-size="240" class="mx-auto mt-5 rounded-xl"></canvas>
                <p class="mt-3 break-all text-xs text-muted">{{ $qr->publicUrl() }}</p>
                <div class="mt-5 flex justify-center gap-2">
                    <button onclick="signoQrDownload('{{ $qr->publicUrl() }}', 'signo-{{ \Illuminate\Support\Str::slug($qr->title) }}')" class="btn-primary">Herunterladen</button>
                    <a href="{{ $qr->publicUrl() }}" target="_blank" class="btn-ghost">Öffnen</a>
                </div>
                <button wire:click="$set('qrPage', null)" class="mt-4 text-sm text-muted hover:text-ink">Schließen</button>
            </div>
        </div>
    @endif
</div>
