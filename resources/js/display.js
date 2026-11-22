// Passt eine TV-Seite automatisch in den Bildschirm ein (verkleinert Schrift bei viel Inhalt).
export function signoFit(root) {
    const pages = (root || document).querySelectorAll('.sg-tv');
    // Während des Messens dürfen Seiten ihre Bühnen-Transformationen abschalten (siehe .sg-measuring)
    const html = document.documentElement;
    html.classList.add('sg-measuring');
    pages.forEach((page) => {
        const box = page.querySelector('.sg-fit');
        if (!box || page.classList.contains('sg-t-media') || !box.offsetWidth) return;
        // Ragt irgendein Element über den Innenrand (inkl. Abstand) hinaus? Skalierung der Bühne wird herausgerechnet.
        const overflows = () => {
            const r = box.getBoundingClientRect();
            const cs = getComputedStyle(box);
            const kx = r.width / box.offsetWidth || 1, ky = r.height / box.offsetHeight || 1;
            const bottom = r.bottom - parseFloat(cs.paddingBottom) * ky + 1;
            const right = r.right - parseFloat(cs.paddingRight) * kx + 1;
            const els = box.querySelectorAll('.sg-head, .sg-section, .sg-item, .sg-foot, .sg-split-text > *, .sg-row, .sg-price');
            for (const el of els) {
                const e = el.getBoundingClientRect();
                if (e.height && (e.bottom > bottom || e.right > right)) return true;
            }
            return box.scrollHeight > box.clientHeight + 1 || box.scrollWidth > box.clientWidth + 1;
        };
        // Wenig Inhalt: Schrift bis 40 % größer; viel Inhalt: kleiner, bis alles passt
        const max = page.classList.contains('sg-t-list') ? 1.4 : 1.15;
        page.style.setProperty('--fit', String(max));
        if (!overflows()) return;
        let lo = 0.35, hi = max;
        for (let i = 0; i < 10; i++) {
            const mid = (lo + hi) / 2;
            page.style.setProperty('--fit', mid.toFixed(4));
            if (overflows()) hi = mid; else lo = mid;
        }
        page.style.setProperty('--fit', lo.toFixed(4));
    });
    html.classList.remove('sg-measuring');
}
window.signoFit = signoFit;

const refit = () => { signoFit(document); if (window.signoTick) window.signoTick(); if (window.signoWeather) window.signoWeather(); };
if (document.fonts && document.fonts.ready) document.fonts.ready.then(refit);
window.addEventListener('load', refit);
let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(refit, 150); });

// ---------- Live-Bausteine: Uhr, Datum, Countdown, Wetter ----------
const pad = (n) => String(n).padStart(2, '0');
function tick() {
    const now = new Date();
    document.querySelectorAll('[data-clock]').forEach((el) => { el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()); });
    document.querySelectorAll('[data-date]').forEach((el) => {
        try { el.textContent = now.toLocaleDateString(el.dataset.date || 'de', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); } catch (e) {}
    });
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const t = new Date(el.dataset.countdown);
        if (isNaN(t)) return;
        let diff = Math.floor((t - now) / 1000);
        if (diff <= 0) {
            if (!el.classList.contains('sg-done')) { el.classList.add('sg-done'); el.textContent = el.dataset.done || '🎉'; }
            return;
        }
        const parts = { d: Math.floor(diff / 86400), h: Math.floor(diff % 86400 / 3600), m: Math.floor(diff % 3600 / 60), s: diff % 60 };
        Object.keys(parts).forEach((k) => { const p = el.querySelector('[data-part="' + k + '"]'); if (p) p.textContent = k === 'd' ? parts[k] : pad(parts[k]); });
    });
}
setInterval(tick, 1000);
document.addEventListener('DOMContentLoaded', tick);
window.signoTick = tick;

const WMO = (c) => c === 0 ? '☀️' : c <= 2 ? '🌤️' : c === 3 ? '☁️' : c <= 48 ? '🌫️' : c <= 67 ? '🌧️' : c <= 77 ? '🌨️' : c <= 82 ? '🌦️' : c <= 86 ? '🌨️' : '⛈️';
const weatherCache = {};
function weather() {
    document.querySelectorAll('[data-weather]').forEach((el) => {
        const city = el.dataset.weather;
        const apply = (w) => {
            if (!w || w.temp === undefined || w.temp === null) return;
            const i = el.querySelector('.sg-w-icon'); const t = el.querySelector('.sg-w-temp');
            if (i) i.textContent = WMO(w.code);
            if (t) t.textContent = Math.round(w.temp) + '°';
        };
        const c = weatherCache[city];
        if (c && Date.now() - c.at < 15 * 60 * 1000) { apply(c.w); return; }
        fetch('/api/weather?city=' + encodeURIComponent(city)).then((r) => r.ok ? r.json() : null).then((w) => {
            weatherCache[city] = { at: Date.now(), w }; apply(w);
        }).catch(() => {});
    });
}
setInterval(weather, 60 * 1000);
document.addEventListener('DOMContentLoaded', weather);
window.signoWeather = weather;
