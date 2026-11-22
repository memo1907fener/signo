<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title ?? null" />
    @livewireStyles
</head>
<body class="min-h-screen font-sans">
    <div class="flex min-h-screen flex-col items-center px-4 py-10">
        <a href="{{ url('/') }}" class="mb-8"><x-logo /></a>
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
        <p class="mt-10 text-xs text-muted">
            <a href="{{ route('legal', 'impressum') }}" class="hover:text-ink">Impressum</a> ·
            <a href="{{ route('legal', 'datenschutz') }}" class="hover:text-ink">Datenschutz</a> ·
            <a href="{{ route('legal', 'agb') }}" class="hover:text-ink">AGB</a>
        </p>
    </div>
    @livewireScripts
</body>
</html>
