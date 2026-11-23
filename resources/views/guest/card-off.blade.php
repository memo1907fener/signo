<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $org->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-paper p-6 text-center font-sans text-ink">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-muted">{{ $org->name }}</p>
        <p class="mt-4 text-xl font-bold">{{ $t['st_off'] }}</p>
    </div>
</body>
</html>
