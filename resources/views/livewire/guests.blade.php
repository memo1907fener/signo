@php
    $toggle = fn ($model) => '<span class="relative inline-flex items-center"><input type="checkbox" wire:model.live="' . $model . '" class="peer sr-only"><span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-green-600"></span><span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span></span>';
@endphp
<div>
    <h1 class="text-3xl font-bold tracking-tight">{{ __('Gäste') }}</h1>
    <p class="mt-1 text-muted">{{ __('Rückmeldungen sammeln und Stammgäste belohnen – beides über einen QR-Code, ohne App.') }}</p>

    {{-- Feedback --}}
    <section class="card mt-6 p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <label class="flex cursor-pointer items-center gap-3">
                {!! $toggle('feedbackOn') !!}
                <span class="text-xl font-bold">⭐ {{ __('Feedback & Bewertungen') }}</span>
            </label>
            @if ($count)
                <div class="text-right"><span class="text-3xl font-extrabold">{{ number_format($avg, 1, ',', '.') }}</span> <span class="text-amber-500">★</span>
                    <span class="block text-xs text-muted">{{ trans_choice(':n Rückmeldung|:n Rückmeldungen', $count, ['n' => $count]) }}</span></div>
            @endif
        </div>

        @if ($feedbackOn)
            <div class="mt-6 grid gap-8 md:grid-cols-[1fr_auto]">
                <div>
                    <label class="label" for="g">{{ __('Link zu Ihren Google-Bewertungen (optional)') }}</label>
                    <div class="flex gap-2">
                        <input id="g" wire:model="googleUrl" wire:blur="saveFeedback" class="input" placeholder="https://g.page/r/…/review">
                        <button wire:click="saveFeedback" class="btn-primary btn-sm shrink-0">{{ __('Speichern') }}</button>
                    </div>
                    @error('googleUrl') <p class="error">{{ $message }}</p> @enderror
                    <p class="hint">{{ __('Nach dem Absenden sehen Gäste einen Knopf „Auf Google bewerten“. Er wird allen Gästen angeboten – so verlangt es Google. Kritik und Kontaktdaten sehen nur Sie.') }}
                        @if ($saved === 'feedback') <span class="font-semibold text-green-700">{{ __('Gespeichert.') }}</span> @endif</p>

                    <p class="label mt-5">{{ __('Link für Gäste') }}</p>
                    <div class="flex gap-2">
                        <input readonly value="{{ $org->feedbackUrl() }}" class="input text-xs" onclick="this.select()">
                        <a href="{{ $org->feedbackUrl() }}" target="_blank" class="btn-ghost btn-sm shrink-0">{{ __('Öffnen') }}</a>
                    </div>
                    <p class="hint">{{ __('Der Link steht automatisch unter jeder Handy-Seite. Den QR-Code können Sie zusätzlich auf Tische oder die Rechnung drucken.') }}</p>
                </div>
                <div class="text-center">
                    <canvas data-qr="{{ $org->feedbackUrl() }}" data-size="170" class="mx-auto rounded-xl ring-1 ring-black/10"></canvas>
                    <button onclick="signoQrDownload('{{ $org->feedbackUrl() }}', 'signo-feedback')" class="btn-ghost btn-sm mt-3">{{ __('QR-Code herunterladen') }}</button>
                </div>
            </div>

            @if ($count)
                <div class="mt-8 grid gap-8 border-t border-black/5 pt-6 md:grid-cols-[220px_1fr]">
                    <div class="space-y-1.5">
                        @foreach ([5, 4, 3, 2, 1] as $r)
                            @php $n = (int) ($dist[$r] ?? 0); @endphp
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-7 tabular-nums">{{ $r }} ★</span>
                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-sand"><span class="block h-full rounded-full {{ $r >= 4 ? 'bg-green-500' : ($r === 3 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ $count ? round($n / $count * 100) : 0 }}%"></span></span>
                                <span class="w-7 text-right tabular-nums text-muted">{{ $n }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-3">
                        @foreach ($list as $f)
                            <div class="rounded-2xl bg-paper p-4 ring-1 ring-black/5" wire:key="f-{{ $f->id }}">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-amber-500">{{ str_repeat('★', $f->rating) }}<span class="text-black/15">{{ str_repeat('★', 5 - $f->rating) }}</span></span>
                                    @if (in_array($f->id, $unread)) <span class="chip bg-brand text-white">{{ __('neu') }}</span> @endif
                                    <span class="ml-auto text-xs text-muted">{{ $f->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</span>
                                    <button wire:click="deleteFeedback({{ $f->id }})" wire:confirm="{{ __('Diese Rückmeldung löschen?') }}" class="text-xs text-muted hover:text-red-600" aria-label="{{ __('Löschen') }}">✕</button>
                                </div>
                                @if ($f->comment) <p class="mt-2 whitespace-pre-line text-sm">{{ $f->comment }}</p> @endif
                                @if ($f->contact) <p class="mt-2 text-xs text-muted">{{ __('Kontakt') }}: {{ $f->contact }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="mt-6 rounded-2xl bg-paper p-4 text-sm text-muted">{{ __('Noch keine Rückmeldungen. Sobald Gäste den QR-Code scannen, erscheinen sie hier.') }}</p>
            @endif
        @else
            <p class="mt-3 text-sm text-muted">{{ __('Gäste bewerten mit einem Tipp von 1 bis 5 Sternen. Kritik landet direkt bei Ihnen, und alle bekommen den Link zu Ihren Google-Bewertungen.') }}</p>
        @endif
    </section>

    {{-- Stempelkarte --}}
    <section class="card mt-6 p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <label class="flex cursor-pointer items-center gap-3">
                {!! $toggle('loyaltyOn') !!}
                <span class="text-xl font-bold">🎟️ {{ __('Digitale Stempelkarte') }}</span>
            </label>
            @if ($stamps)
                <div class="text-right text-sm text-muted"><strong class="text-ink">{{ $cards }}</strong> {{ __('Karten') }} · <strong class="text-ink">{{ $stamps }}</strong> {{ __('Stempel') }} · <strong class="text-ink">{{ $redeemed }}</strong> {{ __('eingelöst') }}</div>
            @endif
        </div>

        @if ($loyaltyOn)
            <div class="mt-6 grid gap-8 md:grid-cols-[1fr_auto]">
                <form wire:submit="saveLoyalty" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label" for="ct">{{ __('Name der Karte') }}</label><input id="ct" wire:model="cardTitle" class="input" placeholder="{{ __('z. B. Kaffee-Karte') }}"></div>
                        <div><label class="label" for="cr">{{ __('Belohnung') }}</label><input id="cr" wire:model="cardReward" class="input" placeholder="{{ __('z. B. 1 Kaffee gratis') }}"></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="cg">{{ __('Stempel bis zur Belohnung') }}</label>
                            <select id="cg" wire:model="cardGoal" class="input">@foreach ([5, 6, 8, 10, 12, 15, 20] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select>
                        </div>
                        <div>
                            <label class="label" for="cp">{{ __('Code des Personals') }}</label>
                            <div class="flex gap-2">
                                <input id="cp" wire:model="cardPin" inputmode="numeric" maxlength="6" class="input font-mono text-lg tracking-[.3em]">
                                <button type="button" wire:click="newPin" class="btn-ghost btn-sm shrink-0">{{ __('Neu') }}</button>
                            </div>
                            @error('cardPin') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button class="btn-primary">{{ __('Speichern') }}</button>
                    @if ($saved === 'loyalty') <span class="ml-2 text-sm font-semibold text-green-700">{{ __('Gespeichert.') }}</span> @endif
                    <div class="rounded-2xl bg-paper p-4 text-sm text-muted ring-1 ring-black/5">
                        <p class="font-semibold text-ink">{{ __('So funktioniert es') }}</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-5">
                            <li>{{ __('Der Gast scannt den QR-Code an der Theke – seine Karte öffnet sich am Handy.') }}</li>
                            <li>{{ __('Beim Bezahlen tippt er auf „Stempel holen“ und reicht das Handy kurz herüber.') }}</li>
                            <li>{{ __('Ihr Personal gibt den Code ein – der Stempel ist drauf.') }}</li>
                        </ol>
                        <p class="mt-2">{{ __('Ändern Sie den Code, sobald ihn Gäste kennen könnten. Je Karte sind höchstens 10 Stempel pro Tag möglich.') }}</p>
                    </div>
                </form>
                <div class="text-center">
                    <canvas data-qr="{{ $org->stampUrl() }}" data-size="170" class="mx-auto rounded-xl ring-1 ring-black/10"></canvas>
                    <button onclick="signoQrDownload('{{ $org->stampUrl() }}', 'signo-stempelkarte')" class="btn-ghost btn-sm mt-3">{{ __('QR-Code herunterladen') }}</button>
                    <a href="{{ $org->stampUrl() }}" target="_blank" class="mt-2 block text-xs text-muted underline">{{ __('Karte ansehen') }}</a>
                </div>
            </div>
        @else
            <p class="mt-3 text-sm text-muted">{{ __('Die Treuekarte am Handy: ohne App, ohne Papier. Gestempelt wird mit einem Code, den nur Ihr Personal kennt.') }}</p>
        @endif
    </section>
</div>
