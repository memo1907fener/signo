<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Signo Bildschirm</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/player.css'])
    <style>
        #stage { position: fixed; top: 0; right: 0; bottom: 0; left: 0; background: #000; }
        .scene { position: absolute; top: 0; right: 0; bottom: 0; left: 0; opacity: 0; visibility: hidden; transition: opacity .9s ease, visibility 0s linear .9s; }
        .scene.on { opacity: 1; visibility: visible; transition: opacity .9s ease, visibility 0s; z-index: 2; }
        #overlay {
            position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #111214; color: #f5f2ec; font-family: "Inter Variable", system-ui, sans-serif; text-align: center; padding: 5vh 5vw;
        }
        #overlay.hidden { display: none; }
        #overlay .logo { display: flex; align-items: center; gap: 1.6vh; font-size: 4vh; font-weight: 800; letter-spacing: -.02em; }
        #overlay .logo svg { width: 5.5vh; height: 5.5vh; }
        #overlay h1 { margin-top: 6vh; font-size: 3.4vh; font-weight: 500; color: #a39d93; }
        #code { margin-top: 2.5vh; font-size: 17vh; font-weight: 800; letter-spacing: .12em; font-variant-numeric: tabular-nums; color: #fff; line-height: 1; }
        #overlay p { margin-top: 4vh; font-size: 2.6vh; color: #a39d93; max-width: 70vw; line-height: 1.5; }
        #overlay p strong { color: #fff; }
        #sleep { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 60; background: #000; display: none; cursor: none; }
        #status { position: fixed; right: 1.2vh; bottom: 1vh; z-index: 70; font: 1.4vh system-ui, sans-serif; color: rgba(255,255,255,.55); background: rgba(0,0,0,.35); padding: .3vh 1vh; border-radius: 1vh; display: none; }
    </style>
</head>
<body>
<div id="stage"></div>
<div id="overlay">
    <div class="logo">
        <svg viewBox="0 0 32 32"><rect width="32" height="32" rx="9" fill="#ff5a36"/><rect x="7" y="9" width="18" height="12" rx="2.5" fill="none" stroke="#fff" stroke-width="2.4"/><circle cx="16" cy="25" r="2" fill="#fff"/></svg>
        <span>signo</span>
    </div>
    <h1 id="ov-title">Verbindung wird hergestellt …</h1>
    <div id="code"></div>
    <p id="ov-text"></p>
</div>
<div id="sleep"></div>
<div id="status"></div>

<script>
(function () {
    'use strict';
    var BASE = @json(url('/'));
    var HOST = BASE.replace(/^https?:\/\//, '');
    var LS = {
        get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
        del: function (k) { try { localStorage.removeItem(k); } catch (e) {} }
    };

    // ---------- Zustand ----------
    var params = new URLSearchParams(location.search);
    var token = params.get('token') || LS.get('signo_token');
    if (params.get('token')) LS.set('signo_token', params.get('token'));

    var deviceId = params.get('device') || LS.get('signo_device');
    if (!deviceId) { deviceId = 'web-' + Math.random().toString(36).slice(2) + Date.now().toString(36); LS.set('signo_device', deviceId); }

    var failCount = 0, unknownCount = 0;
    var pollTimer = null, pollEvery = 30000;
    var currentRevision = null;
    var scenes = [], sceneIndex = -1, sceneTimer = null, seconds = 12;
    var lastTick = Date.now(), bootAt = Date.now();
    var lastGood = null;
    try { lastGood = JSON.parse(LS.get('signo_last') || 'null'); } catch (e) {}

    var $ = function (id) { return document.getElementById(id); };
    var stage = $('stage');

    // ---------- Anzeige-Helfer ----------
    function overlay(title, code, text) {
        $('ov-title').textContent = title || '';
        $('code').textContent = code || '';
        $('code').style.display = code ? 'block' : 'none';
        $('ov-text').innerHTML = text || '';
        $('overlay').classList.remove('hidden');
    }
    function hideOverlay() { $('overlay').classList.add('hidden'); }
    function status(msg) { var s = $('status'); s.textContent = msg || ''; s.style.display = msg ? 'block' : 'none'; }
    function sleep(on) { $('sleep').style.display = on ? 'block' : 'none'; if (on) pauseVideos(); }

    function pauseVideos() { stage.querySelectorAll('video').forEach(function (v) { try { v.pause(); } catch (e) {} }); }

    function fit(el) { if (window.signoFit) { try { window.signoFit(el); } catch (e) {} } }

    // ---------- Szenen ----------
    function build(data) {
        clearTimeout(sceneTimer);
        stage.innerHTML = '';
        scenes = [];
        seconds = Math.max(4, data.seconds || 12);
        (data.pages || []).forEach(function (p) {
            var el = document.createElement('div');
            el.className = 'scene sg-stage';
            el.innerHTML = p.html;
            stage.appendChild(el);
            var v = el.querySelector('video');
            if (v) { v.loop = (data.pages.length === 1); v.removeAttribute('autoplay'); v.pause(); }
            scenes.push(el);
        });
        fit(stage);
        sceneIndex = -1;
        if (!scenes.length) {
            overlay('Noch keine Inhalte', '', 'Fügen Sie unter <strong>' + HOST + '</strong> Seiten hinzu oder wählen Sie Seiten für diesen Bildschirm.');
            return;
        }
        hideOverlay();
        next();
    }

    function next() {
        clearTimeout(sceneTimer);
        lastTick = Date.now();
        if (!scenes.length) return;
        var prev = scenes[sceneIndex];
        sceneIndex = (sceneIndex + 1) % scenes.length;
        var cur = scenes[sceneIndex];
        if (prev !== cur) {
            cur.classList.add('on');
            if (prev) {
                prev.classList.remove('on');
                var pv = prev.querySelector('video');
                if (pv) setTimeout(function () { try { pv.pause(); } catch (e) {} }, 1000);
            }
        } else {
            cur.classList.add('on');
        }
        fit(cur);

        var wait = seconds * 1000;
        var v = cur.querySelector('video');
        if (v) {
            try { v.currentTime = 0; } catch (e) {}
            var pr = v.play(); if (pr && pr.catch) pr.catch(function () {});
            // Video komplett abspielen (bei mehreren Seiten), höchstens 10 Minuten
            if (scenes.length > 1 && isFinite(v.duration) && v.duration > 0) {
                wait = Math.min(600000, Math.max(wait, Math.ceil(v.duration * 1000)));
            }
        }
        if (scenes.length > 1) sceneTimer = setTimeout(next, wait);
        else sceneTimer = setTimeout(function () { lastTick = Date.now(); next(); }, 60000);
    }

    function apply(data) {
        if (data.status !== 'active') return;
        if (data.revision === currentRevision && scenes.length) return;
        currentRevision = data.revision;
        build(data);
        precache(data);
    }

    // Offline: zuletzt gültigen Inhalt weiterspielen
    function keepPlaying(reason) {
        if (scenes.length) { status(reason + ' · läuft offline weiter'); return; }
        if (lastGood) { currentRevision = null; apply(lastGood); status(reason + ' · Offline-Wiedergabe'); return; }
        overlay('Verbindung wird hergestellt …', '', 'Bitte Internetverbindung prüfen.<br><small>' + reason + '</small>');
    }

    // ---------- Server ----------
    function register() {
        overlay('Verbindung wird hergestellt …', '', '');
        return fetch(BASE + '/api/player/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ device_id: deviceId, resolution: screen.width + 'x' + screen.height })
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (j) {
            if (j && j.token) { token = j.token; LS.set('signo_token', token); failCount = 0; }
            else failCount++;
        }).catch(function () { failCount++; });
    }

    function handleCommands(list) {
        (list || []).forEach(function (c) { if (c === 'reload') setTimeout(function () { location.reload(); }, 500); });
    }

    function poll() {
        if (!token) return register().then(function () { if (token) return poll(); });
        var url = BASE + '/api/player/' + encodeURIComponent(token) + '/content?res=' + screen.width + 'x' + screen.height;
        var ctrl = window.AbortController ? new AbortController() : null;
        var to = setTimeout(function () { if (ctrl) ctrl.abort(); }, 20000);

        return fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store', signal: ctrl ? ctrl.signal : undefined })
            .then(function (res) {
                clearTimeout(to);
                var json = (res.headers.get('content-type') || '').indexOf('json') !== -1;
                if (res.status === 404 && json) {
                    return res.json().then(function (b) {
                        if (b && b.status === 'unknown') {
                            unknownCount++;
                            // erst nach zwei eindeutigen Antworten neu koppeln (Bildschirm wurde entfernt)
                            if (unknownCount >= 2 || !scenes.length) {
                                LS.del('signo_token'); LS.del('signo_last'); token = null; lastGood = null;
                                unknownCount = 0; currentRevision = null; scenes = []; stage.innerHTML = '';
                                return register().then(function () { if (token) return poll(); });
                            }
                        }
                        if (b && b.status === 'unknown') { pollEvery = 5000; status('Verbindung wird geprüft …'); return; }
                        failCount++; keepPlaying('Server antwortet nicht');
                    });
                }
                if (!res.ok || !json) { failCount++; keepPlaying('Serverfehler ' + res.status); return; }
                return res.json().then(function (data) {
                    failCount = 0; unknownCount = 0; status('');
                    pollEvery = Math.max(5, data.poll || 30) * 1000;
                    handleCommands(data.commands);

                    if (data.status === 'pairing') {
                        sleep(false); clearTimeout(sceneTimer); stage.innerHTML = ''; scenes = []; currentRevision = null;
                        overlay('Bildschirm verbinden', data.code,
                            'Öffnen Sie <strong>' + HOST + '</strong> → <strong>Bildschirme</strong> → <strong>Bildschirm verbinden</strong> und geben Sie diesen Code ein.');
                    } else if (data.status === 'suspended') {
                        sleep(false); clearTimeout(sceneTimer); stage.innerHTML = ''; scenes = []; currentRevision = null;
                        overlay('Pausiert', '', data.message || '');
                    } else if (data.status === 'sleeping') {
                        sleep(true);
                    } else if (data.status === 'active') {
                        var wasSleeping = $('sleep').style.display === 'block';
                        sleep(false);
                        lastGood = data;
                        LS.set('signo_last', JSON.stringify(data));
                        apply(data);
                        if (wasSleeping && scenes.length) { sceneIndex = -1; next(); }
                    }
                });
            })
            .catch(function (e) { clearTimeout(to); failCount++; keepPlaying('Keine Verbindung'); });
    }

    function loop() {
        clearTimeout(pollTimer);
        Promise.resolve(poll()).then(function () {}, function () {}).then(function () {
            var wait = failCount > 0 ? Math.min(120000, 5000 * Math.pow(2, Math.min(5, failCount - 1))) : pollEvery;
            pollTimer = setTimeout(loop, wait);
        });
    }

    // ---------- Medien vorab laden (Service Worker legt sie im Cache ab) ----------
    function precache(data) {
        if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) return;
        var urls = [];
        (data.pages || []).forEach(function (p) { (p.media || []).forEach(function (u) { urls.push(u); }); });
        if (urls.length) navigator.serviceWorker.controller.postMessage({ type: 'precache', urls: urls });
    }

    // ---------- Robustheit ----------
    // Bildschirm wach halten
    var wakeLock = null;
    function keepAwake() {
        if (!('wakeLock' in navigator)) return;
        navigator.wakeLock.request('screen').then(function (l) { wakeLock = l; }).catch(function () {});
    }
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') { keepAwake(); fit(stage); } });

    // Hängt die Wiedergabe? -> Seite neu laden
    setInterval(function () {
        if (scenes.length && (Date.now() - lastTick) > 15 * 60 * 1000 && navigator.onLine !== false) location.reload();
    }, 60000);

    // Nächtlicher Neustart gegen Speicherlecks (03:30, nach mind. 6 Std. Laufzeit)
    setInterval(function () {
        var n = new Date();
        if (n.getHours() === 3 && n.getMinutes() >= 30 && n.getMinutes() < 35 && (Date.now() - bootAt) > 6 * 3600 * 1000) location.reload();
    }, 60000);

    window.addEventListener('online', function () { failCount = 0; loop(); });
    window.addEventListener('resize', function () { fit(stage); });
    window.addEventListener('error', function () { /* niemals stehen bleiben */ });

    // Doppelklick / OK-Taste: Vollbild
    function fullscreen() { var d = document.documentElement; if (!document.fullscreenElement && d.requestFullscreen) d.requestFullscreen().catch(function () {}); }
    document.addEventListener('dblclick', fullscreen);
    document.addEventListener('keydown', function (e) { if (e.key === 'Enter') fullscreen(); });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }

    // ---------- Start ----------
    keepAwake();
    if (token && lastGood && lastGood.status === 'active') apply(lastGood);   // sofort zeigen, auch offline
    loop();
})();
</script>
@vite(['resources/js/display.js'])
</body>
</html>
