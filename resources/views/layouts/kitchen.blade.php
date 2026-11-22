<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title ?? 'Küche'" />
    <meta name="robots" content="noindex">
    @livewireStyles
</head>
<body class="min-h-screen bg-ink font-sans text-white">
    <main class="p-4 sm:p-6">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
