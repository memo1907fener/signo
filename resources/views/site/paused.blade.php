<!DOCTYPE html>
<html lang="de">
<head><x-head title="{{ __('Pausiert') }}" /></head>
<body class="flex min-h-screen items-center justify-center p-6 text-center font-sans">
    <div>
        <x-logo />
        <h1 class="mt-8 text-2xl font-bold">{{ __('Diese Seite ist gerade nicht verfügbar') }}</h1>
        <p class="mt-2 text-muted">{{ __(':name hat die Seite pausiert. Bitte versuchen Sie es später erneut.', ['name' => $org->name]) }}</p>
    </div>
</body>
</html>
