<!DOCTYPE html>
<html lang="de">
<head><x-head title="{{ __('Konto gesperrt') }}" /></head>
<body class="flex min-h-screen items-center justify-center p-6 text-center font-sans">
    <div class="max-w-md">
        <x-logo />
        <h1 class="mt-8 text-2xl font-bold">{{ __('Ihr Konto ist gesperrt') }}</h1>
        <p class="mt-2 text-muted">{{ __('Bitte kontaktieren Sie uns unter') }} <a class="underline" href="mailto:{{ config('signo.company.email') }}">{{ config('signo.company.email') }}</a>.</p>
    </div>
</body>
</html>
