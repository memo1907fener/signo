<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Abholung · {{ $org->name }}</title>
    @vite(['resources/css/player.css'])
    <style>
        html, body { background: {{ $theme === 'dark' ? '#111214' : '#f7f4ef' }}; color: {{ $theme === 'dark' ? '#f5f2ec' : '#17140f' }}; cursor: none; }
        .wrap { position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; font-family: "Inter Variable", system-ui, sans-serif; padding: 5vh 4vw; gap: 4vw; }
        .col { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .col h2 { font-size: 4.2vh; font-weight: 750; letter-spacing: -.01em; display: flex; align-items: baseline; gap: 1.5vh; }
        .col h2 small { font-size: 2.3vh; font-weight: 500; opacity: .55; }
        .bar { height: .8vh; width: 10vh; border-radius: 99px; margin: 2vh 0 4vh; }
        .nums { display: flex; flex-wrap: wrap; gap: 2.2vh; align-content: flex-start; }
        .n { font-size: 8vh; font-weight: 800; line-height: 1; padding: 2vh 3vh; border-radius: 2.4vh; font-variant-numeric: tabular-nums; }
        .prep .n { background: {{ $theme === 'dark' ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.05)' }}; }
        .ready .n { background: #2f9e62; color: #fff; animation: pop .6s ease; }
        .ready .n { font-size: 11vh; }
        @keyframes pop { from { transform: scale(.6); opacity: 0 } to { transform: scale(1); opacity: 1 } }
        .empty { font-size: 2.6vh; opacity: .4; }
        .divider { width: .3vh; background: {{ $theme === 'dark' ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)' }}; }
        @media (orientation: portrait) { .wrap { flex-direction: column; } .divider { width: auto; height: .3vh; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="col prep">
        <h2>In Zubereitung <small>Preparing</small></h2>
        <div class="bar" style="background:#d4a017"></div>
        <div class="nums" id="prep"></div>
    </div>
    <div class="divider"></div>
    <div class="col ready">
        <h2>Bitte abholen <small>Ready</small></h2>
        <div class="bar" style="background:#2f9e62"></div>
        <div class="nums" id="ready"></div>
    </div>
</div>
<script>
(function () {
    var url = @json(route('board.json', $token));
    var lastJson = '';
    function fill(id, list, empty) {
        var el = document.getElementById(id);
        if (!list.length) { el.innerHTML = '<p class="empty">' + empty + '</p>'; return; }
        el.innerHTML = list.map(function (n) { return '<div class="n">' + n + '</div>'; }).join('');
    }
    function poll() {
        fetch(url, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
            var js = JSON.stringify(j); if (js === lastJson) return; lastJson = js;
            fill('prep', j.preparing || [], '–');
            fill('ready', j.ready || [], '–');
        }).catch(function () {}).then(function () { setTimeout(poll, 5000); });
    }
    poll();
})();
</script>
</body>
</html>
