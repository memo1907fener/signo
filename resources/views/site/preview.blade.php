<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vorschau · {{ $page->title }}</title>
    @vite(['resources/css/player.css'])
    <style>
        body { display: flex; align-items: center; justify-content: center; cursor: auto; background: #1a1a1a; }
        .frame { position: relative; {{ $portrait ? 'height: 100vh; aspect-ratio: 9/16;' : 'width: 100vw; height: 100vh;' }} }
        .hint { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); font: 13px system-ui; color: #fff; background: rgba(0,0,0,.6); padding: 6px 12px; border-radius: 99px; z-index: 9; }
    </style>
</head>
<body>
    <div class="hint" onclick="this.remove()">Vorschau {{ $portrait ? 'Hochformat' : '' }} – Doppelklick für Vollbild</div>
    <div class="frame sg-stage" style="{{ $portrait ? 'width: auto; height: 100vh; aspect-ratio: 9/16;' : '' }}">@include('display.page', ['page' => $page, 'mode' => 'tv'])</div>
    <script>document.addEventListener('dblclick', () => document.documentElement.requestFullscreen && document.documentElement.requestFullscreen());</script>
    @vite(['resources/js/display.js'])
</body>
</html>
