<div>
    <h1 class="text-3xl font-bold tracking-tight">Admin</h1>
    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($stats as $label => $n)
            <div class="card p-5"><p class="text-sm text-muted">{{ $label }}</p><p class="mt-1 text-3xl font-bold">{{ $n }}</p></div>
        @endforeach
    </div>

    <input wire:model.live.debounce.400ms="q" class="input mt-8 max-w-sm" placeholder="Suche nach Betrieb oder E-Mail">

    <div class="card mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wide text-muted">
                <tr><th class="px-5 py-3">Betrieb</th><th class="px-3 py-3">Branche</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Seiten / Bildschirme</th><th class="px-3 py-3">Seit</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-black/5">
                @foreach ($orgs as $o)
                    <tr wire:key="o-{{ $o->id }}" class="{{ $o->locked_at ? 'bg-red-50/60' : '' }}">
                        <td class="px-5 py-3">
                            <p class="font-semibold">{{ $o->name }}</p>
                            <p class="text-xs text-muted">{{ $o->users->pluck('email')->implode(', ') }}</p>
                        </td>
                        <td class="px-3 py-3">{{ $o->industryLabel() }}</td>
                        <td class="px-3 py-3">
                            @if ($o->locked_at) <span class="chip bg-red-100 text-red-700">Gesperrt</span>
                            @elseif ($o->hasSubscription()) <span class="chip bg-green-100 text-green-800">{{ $o->planName() }}</span>
                            @elseif ($o->onTrial()) <span class="chip bg-amber-100 text-amber-800">Test · {{ $o->trialDaysLeft() }} T.</span>
                            @else <span class="chip bg-black/5 text-muted">Abgelaufen</span> @endif
                        </td>
                        <td class="px-3 py-3">{{ $o->pages_count }} / {{ $o->screens_count }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">{{ $o->created_at->format('d.m.Y') }}</td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                <button wire:click="extend({{ $o->id }})" class="btn-ghost btn-sm">+14 Tage</button>
                                <button wire:click="toggleLock({{ $o->id }})" class="btn-ghost btn-sm">{{ $o->locked_at ? 'Entsperren' : 'Sperren' }}</button>
                                @if ($o->id !== auth()->user()->organization_id)
                                    <button wire:click="remove({{ $o->id }})" wire:confirm="{{ $o->name }} mit allen Daten löschen?" class="btn-danger btn-sm">Löschen</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orgs->links() }}</div>
</div>
