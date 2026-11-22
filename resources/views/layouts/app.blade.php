<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title ?? null" />
    @livewireStyles
</head>
<body class="min-h-screen font-sans">
@php
    $user = auth()->user();
    $org = $user?->organization;
    $nav = [
        ['route' => 'pages.index', 'match' => 'pages.*', 'label' => 'Inhalte'],
        ['route' => 'screens.index', 'match' => 'screens.*', 'label' => 'Bildschirme'],
        ['route' => 'account', 'match' => 'account*', 'label' => 'Konto'],
    ];
    if ($user?->is_admin) $nav[] = ['route' => 'admin', 'match' => 'admin*', 'label' => 'Admin'];
@endphp

<header class="sticky top-0 z-30 border-b border-black/5 bg-paper/85 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6">
        <a href="{{ route('pages.index') }}" wire:navigate><x-logo /></a>
        <nav class="flex flex-1 items-center gap-1 overflow-x-auto">
            @foreach ($nav as $n)
                <a href="{{ route($n['route']) }}" wire:navigate
                   class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ request()->routeIs($n['match']) ? 'bg-ink text-white' : 'text-ink/70 hover:bg-black/5 hover:text-ink' }}">
                    {{ $n['label'] }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
            @csrf
            <button class="text-sm text-muted hover:text-ink">Abmelden</button>
        </form>
    </div>
</header>

@if ($org && $org->onTrial())
    <div class="bg-ink text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm sm:px-6">
            <span>Testphase: noch <strong>{{ $org->trialDaysLeft() }} {{ $org->trialDaysLeft() === 1 ? 'Tag' : 'Tage' }}</strong> kostenlos.</span>
            <a href="{{ route('account') }}#paket" wire:navigate class="font-semibold text-brand hover:underline">Paket wählen →</a>
        </div>
    </div>
@elseif ($org && ! $org->isActive())
    <div class="bg-brand text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm sm:px-6">
            <span>Ihre Testphase ist abgelaufen – Bildschirme und QR-Seiten sind pausiert.</span>
            <a href="{{ route('account') }}#paket" wire:navigate class="font-semibold underline">Jetzt Paket wählen</a>
        </div>
    </div>
@endif

<main class="mx-auto max-w-6xl px-4 pb-24 pt-8 sm:px-6">
    @if (session('ok'))
        <div class="mb-6 rounded-2xl bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">{{ session('ok') }}</div>
    @endif
    {{ $slot }}
</main>

<form method="POST" action="{{ route('logout') }}" class="pb-10 text-center sm:hidden">
    @csrf
    <button class="text-sm text-muted">Abmelden</button>
</form>

@livewireScripts
</body>
</html>
