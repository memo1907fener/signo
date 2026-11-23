import './display.js';
import QRCode from 'qrcode';

const root = document.documentElement;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const smoothstep = (e0, e1, v) => { const x = clamp((v - e0) / (e1 - e0)); return x * x * (3 - 2 * x); };
const lerp = (a, b, t) => a + (b - a) * t;
const segment = (s, a, b, c, d) => { const enter = smoothstep(a, b, s), exit = smoothstep(c, d, s); return { enter, exit, active: enter * (1 - exit) }; };
const $ = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => Array.from(el.querySelectorAll(sel));

// ---------- Handy-Vorschauen: 375 px breite Seite in den Rahmen einpassen ----------
function scalePhones() {
    $$('.phone-screen').forEach((box) => {
        const k = box.clientWidth / 375;
        $$('.phone-scale', box).forEach((inner) => { inner.style.transform = 'scale(' + k + ')'; });
    });
}
const refit = () => requestAnimationFrame(() => { if (window.signoFit) window.signoFit(document); scalePhones(); });
window.addEventListener('resize', refit);

// ---------- Auftritt: Elemente erscheinen, Leuchtstift zieht nach, Zahlen zählen hoch ----------
function countUp(el) {
    const end = parseFloat(el.dataset.count);
    if (reduce.matches) { el.firstChild.textContent = String(end); return; }
    const t0 = performance.now(), dur = 1400;
    const step = (t) => {
        const p = clamp((t - t0) / dur);
        el.firstChild.textContent = String(Math.round(end * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}
function initReveals() {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (!e.isIntersecting) return;
        e.target.classList.add('in');
        if (e.target.dataset.count !== undefined) countUp(e.target);
        io.unobserve(e.target);
    }), { threshold: 0.18, rootMargin: '0px 0px -6% 0px' });
    $$('.rv, .mkw, [data-count]').forEach((el) => io.observe(el));
}

// ---------- Wechselnde Wörter ----------
function initSlots() {
    $$('[data-slot]').forEach((slot) => {
        const words = $$('span', slot);
        if (words.length < 2 || reduce.matches) return;
        let i = 0;
        setInterval(() => {
            const cur = words[i];
            i = (i + 1) % words.length;
            cur.classList.remove('on'); cur.classList.add('out');
            words[i].classList.remove('out'); words[i].classList.add('on');
            setTimeout(() => cur.classList.remove('out'), 750);
        }, Number(slot.dataset.slot) || 2400);
    });
}

// ---------- Lichtkegel auf den Kacheln ----------
function initSpotlight() {
    if (!fine) return;
    $$('.tile').forEach((tile) => tile.addEventListener('pointermove', (e) => {
        const r = tile.getBoundingClientRect();
        tile.style.setProperty('--sx', (e.clientX - r.left) + 'px');
        tile.style.setProperty('--sy', (e.clientY - r.top) + 'px');
    }));
}

// ---------- QR-Codes ----------
function initQr() {
    $$('canvas[data-qr]').forEach((c) => QRCode.toCanvas(c, c.dataset.qr, { width: 208, margin: 0, color: { dark: '#0a0b1e', light: '#ffffff' } }));
}

// ---------- Die Scroll-Regie: ein einziger Takt für alles ----------
function initScroll() {
    const hero = $('.hero');
    const band = $('.band-row');
    const story = $('.story');
    const stage = story && $('.story-stage', story);
    const chaps = story ? $$('.chap', story) : [];
    const rails = story ? $$('.rail i', story) : [];
    const langs = story ? $$('.s-langs b', story) : [];
    const manifest = $('.manifest p');
    const words = manifest ? $$('.w', manifest) : [];
    const final = $('.final');
    const chips = $$('.chip');

    let sSmooth = 0, sInit = false, px = 0, py = 0, tpx = 0, tpy = 0, bandX = 0, lastT = performance.now(), lastY = window.scrollY, bandW = 0, lastLang = -1;
    const measure = () => { bandW = band ? band.scrollWidth / 2 : 0; };
    measure(); window.addEventListener('resize', measure);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);

    if (fine) window.addEventListener('pointermove', (e) => { tpx = e.clientX / innerWidth - 0.5; tpy = e.clientY / innerHeight - 0.5; }, { passive: true });

    const set = (el, k, v) => el.style.setProperty(k, v);

    function frame(t) {
        const rm = reduce.matches;
        const vh = innerHeight, y = window.scrollY;
        const dt = Math.min(64, t - lastT); lastT = t;

        // Hero: Bildschirm richtet sich auf
        if (hero && y < vh * 2) set(hero, '--hx', rm ? '0' : (1 - smoothstep(0, vh * 0.7, y)).toFixed(4));

        // Zeiger-Parallaxe
        px = rm ? 0 : lerp(px, tpx, 0.08); py = rm ? 0 : lerp(py, tpy, 0.08);
        if (chips.length && y < vh * 1.6) chips.forEach((c) => { set(c, '--px', px.toFixed(4)); set(c, '--py', py.toFixed(4)); });

        // Laufband: zieht langsam, Scrollen schiebt an
        if (band && bandW) {
            const r = band.getBoundingClientRect();
            if (r.top < vh && r.bottom > 0) {
                bandX += rm ? 0 : dt * 0.045 + Math.abs(y - lastY) * 0.55;
                if (bandX > bandW) bandX -= bandW;
                set(band, '--bx', bandX.toFixed(1));
            }
        }
        lastY = y;

        // Scroll-Geschichte
        const sr = story ? story.getBoundingClientRect() : null;
        if (story && sr.top < vh * 1.5 && sr.bottom > -vh * 0.5) {
            const target = clamp(-sr.top, 0, story.offsetHeight - vh);
            sSmooth = (!sInit || rm) ? target : lerp(sSmooth, target, 0.16);
            if (Math.abs(sSmooth - target) < 0.1) sSmooth = target;
            sInit = true;
            const s = sSmooth;

            const paperExit = smoothstep(680, 1000, s);
            const tvIn = smoothstep(760, 1060, s);
            const side = smoothstep(1700, 2000, s);
            const print = smoothstep(980, 1560, s);
            const en = segment(s, 2700, 2820, 2960, 3060);
            const itl = smoothstep(2960, 3060, s);

            set(stage, '--beam', smoothstep(60, 620, s).toFixed(4));
            set(stage, '--beam-o', segment(s, 30, 110, 570, 660).active.toFixed(4));
            set(stage, '--frame-o', (1 - smoothstep(560, 700, s)).toFixed(4));
            set(stage, '--paper-o', (1 - smoothstep(820, 1000, s)).toFixed(4));
            set(stage, '--paper-s', (1 - paperExit * 0.6).toFixed(4));
            set(stage, '--paper-r', (-4 + paperExit * 4).toFixed(2) + 'deg');
            set(stage, '--tv-o', tvIn.toFixed(4));
            set(stage, '--tv-y', ((1 - tvIn) * 70).toFixed(1) + 'px');
            set(stage, '--tv-s', (0.9 + tvIn * 0.1 - side * 0.12).toFixed(4));
            set(stage, '--tv-x', (side * -7).toFixed(2));
            set(stage, '--print', (100 - print * 100).toFixed(2));
            set(stage, '--printline', segment(s, 980, 1010, 1530, 1570).active.toFixed(3));
            set(stage, '--phone-x', ((1 - side) * 130).toFixed(1));
            set(stage, '--phone-o', side.toFixed(4));
            set(stage, '--qr', smoothstep(2020, 2200, s).toFixed(4));
            set(stage, '--lang', smoothstep(2560, 2760, s).toFixed(4));
            set(stage, '--en', Math.max(en.enter - itl, 0).toFixed(4));
            set(stage, '--itl', itl.toFixed(4));
            set(stage, '--ticket', smoothstep(3020, 3160, s).toFixed(4));
            // Handy bleibt auf Englisch, sobald es gewechselt hat
            const pEn = $('.s-phone .p-en', stage); if (pEn) pEn.style.opacity = en.enter.toFixed(3);

            const lang = s < 2760 ? 0 : (s < 3010 ? 1 : 2);
            if (lang !== lastLang) { langs.forEach((b, i) => b.classList.toggle('on', i === lang)); lastLang = lang; }

            const win = [
                { o: 1 - smoothstep(620, 820, s), y: -smoothstep(620, 820, s) * 40 },
                segment(s, 760, 1020, 1560, 1760),
                segment(s, 1680, 1940, 2400, 2600),
                { enter: smoothstep(2520, 2780, s), exit: 0, active: smoothstep(2520, 2780, s) },
            ];
            chaps.forEach((c, i) => {
                const w = win[i];
                const o = i === 0 ? w.o : w.active;
                const yy = i === 0 ? w.y : (1 - w.enter) * 44 - w.exit * 44;
                set(c, '--o', o.toFixed(4)); set(c, '--y', yy.toFixed(1) + 'px');
            });
            const marks = [0, 760, 1680, 2520, 3200];
            rails.forEach((r, i) => set(r, '--f', clamp((s - marks[i]) / (marks[i + 1] - marks[i])).toFixed(4)));
        }

        // Manifest: Worte leuchten auf
        if (manifest) {
            const r = manifest.getBoundingClientRect();
            if (r.top < vh && r.bottom > -vh * 0.2) {
                const p = clamp((vh * 0.82 - r.top) / (r.height + vh * 0.3));
                const n = words.length;
                words.forEach((w, i) => set(w, '--w', clamp(p * (n + 4) - i).toFixed(3)));
            }
        }

        // Sonnenaufgang am Ende
        if (final) {
            const r = final.getBoundingClientRect();
            set(root, '--dawn', smoothstep(vh * 1.05, vh * 0.2, r.top).toFixed(4));
        }

        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
}

// ---------- Start ----------
document.addEventListener('DOMContentLoaded', () => {
    scalePhones();
    initReveals(); initSlots(); initSpotlight(); initQr(); initScroll();
    const go = () => { root.classList.add('loaded'); refit(); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(go); else go();
    setTimeout(go, 1200);
});

// ---------- Hero: Bildschirm wechselt die Seiten ----------
window.signoHero = function (count) {
    return {
        i: 0,
        init() {
            refit();
            if (reduce.matches || count < 2) return;
            setTimeout(() => {
                $$('.first', this.$el).forEach((el) => el.classList.remove('first'));
                setInterval(() => { this.i = (this.i + 1) % count; refit(); }, 4200);
            }, 4200);
        },
    };
};

// ---------- Live-Demo ----------
window.signoDemo = function (initialText, extra) {
    return {
        text: initialText,
        theme: 'dark',
        accent: 'gold',
        font: 'elegant',
        busy: false,
        error: '',
        timer: null,
        touched: false,
        init() {
            this.render();
            ['text', 'theme', 'accent', 'font'].forEach((k) => this.$watch(k, () => this.queue()));
            // Zeigt von selbst, dass es lebt: tippt eine Zeile dazu, sobald die Demo sichtbar wird
            if (extra && !reduce.matches && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((en) => {
                    if (!en[0].isIntersecting) return;
                    io.disconnect();
                    setTimeout(() => this.autotype(extra), 900);
                }, { threshold: 0.45 });
                io.observe(this.$el);
            }
        },
        autotype(str) {
            let n = 0;
            const tick = () => {
                if (this.touched || n >= str.length) return;
                this.text += str[n++];
                const ta = this.$refs.ta; if (ta) ta.scrollTop = ta.scrollHeight;
                setTimeout(tick, 34 + Math.random() * 50);
            };
            tick();
        },
        queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.render(), 240); },
        token() { const m = $('meta[name=csrf-token]'); return m ? m.content : ''; },
        show(j) {
            this.$refs.tv.innerHTML = j.tv;
            this.$refs.phone.innerHTML = j.mobile;
            refit();
        },
        async render() {
            try {
                const r = await fetch('/demo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token() },
                    body: JSON.stringify({ text: this.text, theme: this.theme, accent: this.accent, font: this.font }),
                });
                if (r.ok) this.show(await r.json());
            } catch (e) {}
        },
        async photo(ev) {
            const f = ev.target.files[0];
            if (!f) return;
            this.touched = true; this.busy = true; this.error = '';
            const fd = new FormData();
            fd.append('photo', f); fd.append('theme', this.theme); fd.append('accent', this.accent); fd.append('font', this.font);
            try {
                const r = await fetch('/demo/foto', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token() }, body: fd });
                const j = await r.json().catch(() => ({}));
                if (r.ok) this.show(j); else this.error = j.message || (window.SIGNO_T || {}).photo || '';
            } catch (e) { this.error = (window.SIGNO_T || {}).offline || ''; }
            this.busy = false;
            ev.target.value = '';
        },
    };
};

// ---------- Branchen ----------
window.signoIndustries = function (first) {
    return {
        ind: first,
        page: 0,
        pick(k) { this.ind = k; this.page = 0; refit(); },
        go(n) { this.page = n; refit(); },
    };
};

// ---------- Preisrechner ----------
window.signoCalc = function (plans) {
    return {
        n: 1,
        get plan() {
            if (this.n === 0) return { key: 'qr', name: plans.qr.name, price: plans.qr.price, interval: 'year', features: plans.qr.features };
            if (this.n === 1) return { key: 'screen', name: plans.screen.name, price: plans.screen.price, interval: 'month', features: plans.screen.features };
            if (this.n <= 5) return { key: 'multi', name: plans.multi.name, price: plans.multi.price, interval: 'month', features: plans.multi.features };
            return { key: 'custom', name: (window.SIGNO_T || {}).customName, price: null, interval: 'month', features: (window.SIGNO_T || {}).customFeatures || [] };
        },
        euro(c) { return (c / 100).toLocaleString('de-AT', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' €'; },
        perScreen() { const p = this.plan; return p.price && this.n > 1 ? this.euro(Math.round(p.price / this.n)) : null; },
    };
};
