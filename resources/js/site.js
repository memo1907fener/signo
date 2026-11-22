import './display.js';

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Kopfzeile bekommt beim Scrollen einen Hintergrund
const top = document.querySelector('.top');
const onScroll = () => {
    if (top) top.classList.toggle('scrolled', window.scrollY > 30);
    // Der Bildschirm dreht sich beim Scrollen zum Besucher
    const hero = document.querySelector('.hero');
    if (hero && !reduce) {
        const p = Math.min(1, Math.max(0, window.scrollY / (hero.offsetHeight * 0.55)));
        hero.style.setProperty('--tilt', (1 - p).toFixed(3));
        hero.style.setProperty('--lit', p.toFixed(3));
    }
};
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

// Handy-Vorschauen: 375 px breite Seite in den Rahmen einpassen
function scalePhones() {
    document.querySelectorAll('.phone-screen').forEach((box) => {
        const inner = box.querySelector('.phone-scale');
        if (inner) inner.style.transform = 'scale(' + (box.clientWidth / 375) + ')';
    });
}
window.addEventListener('resize', scalePhones);
document.addEventListener('DOMContentLoaded', scalePhones);
window.signoScalePhones = scalePhones;

// Einblenden
if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    }), { threshold: 0.2 });
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el)));
} else {
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('in')));
}

const refit = () => requestAnimationFrame(() => { if (window.signoFit) window.signoFit(document); scalePhones(); });

// ---------- Hero: Bildschirm wechselt die Seiten ----------
window.signoHero = function (count) {
    return {
        i: 0,
        init() {
            this.$el.classList.add('boot');
            refit();
            if (reduce || count < 2) { this.$el.classList.add('booted'); return; }
            setTimeout(() => {
                this.$el.classList.remove('boot');
                this.$el.classList.add('booted');
                setInterval(() => { this.i = (this.i + 1) % count; refit(); }, 4200);
            }, 3800);
        },
    };
};

// ---------- Live-Demo ----------
window.signoDemo = function (initialText) {
    return {
        text: initialText,
        theme: 'dark',
        accent: 'gold',
        font: 'elegant',
        busy: false,
        error: '',
        timer: null,
        init() {
            this.render();
            ['text', 'theme', 'accent', 'font'].forEach((k) => this.$watch(k, () => this.queue()));
        },
        queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.render(), 280); },
        token() { const m = document.querySelector('meta[name=csrf-token]'); return m ? m.content : ''; },
        show(j) {
            this.$refs.tv.innerHTML = j.tv;
            this.$refs.phone.innerHTML = j.mobile;
            refit();
        },
        async render() {
            this.error = '';
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
            this.busy = true; this.error = '';
            const fd = new FormData();
            fd.append('photo', f); fd.append('theme', this.theme); fd.append('accent', this.accent); fd.append('font', this.font);
            try {
                const r = await fetch('/demo/foto', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token() }, body: fd });
                const j = await r.json().catch(() => ({}));
                if (r.ok) this.show(j); else this.error = j.message || 'Das Foto konnte nicht gelesen werden.';
            } catch (e) { this.error = 'Keine Verbindung.'; }
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
            return { key: 'custom', name: 'Viele Bildschirme', price: null, interval: 'month', features: ['Mehrere Standorte in einem Konto', 'Eigene Konditionen', 'Persönliche Einrichtung'] };
        },
        euro(c) { return (c / 100).toLocaleString('de-AT', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' €'; },
        perScreen() { const p = this.plan; return p.price && this.n > 0 ? this.euro(Math.round(p.price / this.n)) : null; },
    };
};
