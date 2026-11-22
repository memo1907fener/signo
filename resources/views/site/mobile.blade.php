<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $page->title }} · {{ $org->name }}</title>
    <meta name="theme-color" content="{{ $page->theme === 'dark' ? '#111214' : '#f7f4ef' }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/player.css'])
    <style>
        html, body { background: {{ $page->theme === 'dark' ? '#111214' : '#f7f4ef' }}; overflow: auto; cursor: auto; height: auto; }
        .sg-nav { position: sticky; top: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 12px 16px; backdrop-filter: blur(10px);
            background: {{ $page->theme === 'dark' ? 'rgba(17,18,20,.85)' : 'rgba(247,244,239,.85)' }}; font-family: "Inter Variable", system-ui, sans-serif; }
        .sg-nav a { white-space: nowrap; font-size: 14px; font-weight: 600; padding: 7px 14px; border-radius: 99px; text-decoration: none;
            color: {{ $page->theme === 'dark' ? '#f5f2ec' : '#17140f' }}; border: 1px solid {{ $page->theme === 'dark' ? 'rgba(255,255,255,.18)' : 'rgba(0,0,0,.12)' }}; }
        .sg-nav a.on { background: {{ $page->accentColor() }}; border-color: transparent; color: #fff; }
        .sg-org { text-align: center; padding: 18px 16px 0; font: 600 13px "Inter Variable", system-ui, sans-serif; letter-spacing: .06em; text-transform: uppercase;
            color: {{ $page->theme === 'dark' ? '#a39d93' : '#6e675d' }}; }
        .sg-made { text-align: center; padding: 0 0 28px; font: 12px "Inter Variable", system-ui, sans-serif; }
        .sg-made a { color: {{ $page->theme === 'dark' ? '#6f6a63' : '#9a9388' }}; text-decoration: none; }
    </style>
</head>
<body>
    @if ($others->count() > 1)
        <nav class="sg-nav">
            @foreach ($others as $o)
                <a href="{{ url('/m/' . $o->slug) }}" class="{{ $o->id === $page->id ? 'on' : '' }}">{{ $o->title }}</a>
            @endforeach
        </nav>
    @endif
    <div class="sg-org">{{ $org->name }}</div>
    @include('display.page', ['page' => $page, 'mode' => 'mobile'])
    <div class="sg-made"><a href="{{ url('/') }}">Erstellt mit Signo</a></div>
</body>
</html>
