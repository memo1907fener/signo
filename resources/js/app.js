import './display.js';
import QRCode from 'qrcode';

// QR-Codes: <canvas data-qr="https://...">
function drawQr(root) {
    (root || document).querySelectorAll('[data-qr]').forEach((el) => {
        if (el.dataset.qrDone === el.dataset.qr) return;
        QRCode.toCanvas(el, el.dataset.qr, { width: Number(el.dataset.size || 220), margin: 1, color: { dark: '#17140f', light: '#ffffff' } });
        el.dataset.qrDone = el.dataset.qr;
    });
}
window.signoQrDownload = function (url, name) {
    QRCode.toDataURL(url, { width: 1200, margin: 2 }).then((data) => {
        const a = document.createElement('a');
        a.href = data; a.download = (name || 'signo-qr') + '.png'; a.click();
    });
};

function refresh() { drawQr(document); if (window.signoFit) window.signoFit(document); }
document.addEventListener('DOMContentLoaded', refresh);
document.addEventListener('livewire:navigated', refresh);
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', () => requestAnimationFrame(refresh));
});

// Spracheingabe (Web Speech API; Chrome, Edge, Safari, Android)
window.signoVoice = function () {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    return {
        supported: !!SR,
        listening: false,
        rec: null,
        toggle() {
            if (this.listening) { this.rec && this.rec.stop(); return; }
            this.rec = new SR();
            this.rec.lang = 'de-AT';
            this.rec.interimResults = true;
            this.rec.continuous = false;
            let finalText = '';
            this.rec.onresult = (e) => {
                let text = '';
                for (const r of e.results) text += r[0].transcript;
                this.$refs.voiceInput.value = text;
                if (e.results[e.results.length - 1].isFinal) finalText = text;
            };
            this.rec.onend = () => {
                this.listening = false;
                if (finalText) { this.$wire.set('voiceText', finalText, false); this.$wire.voiceRun(); }
            };
            this.rec.onerror = () => { this.listening = false; };
            this.listening = true;
            this.rec.start();
        },
    };
};

// Küche: Ton bei neuer Bestellung
window.signoKitchen = function (initial) {
    let ctx = null;
    let stored = false;
    try { stored = localStorage.getItem('signo_kitchen_sound') === '1'; } catch (e) {}
    return {
        sound: stored,
        last: initial,
        enable() {
            this.sound = !this.sound;
            try { localStorage.setItem('signo_kitchen_sound', this.sound ? '1' : '0'); } catch (e) {}
            if (this.sound) this.beep();
        },
        beep() {
            try {
                ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
                [0, 0.25, 0.5].forEach((t) => {
                    const o = ctx.createOscillator(); const g = ctx.createGain();
                    o.frequency.value = 880; o.connect(g); g.connect(ctx.destination);
                    g.gain.setValueAtTime(0.25, ctx.currentTime + t);
                    g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.2);
                    o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.2);
                });
            } catch (e) {}
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        },
        watch() {
            setInterval(() => {
                const now = Number(this.$el.dataset.last || 0);
                if (now > this.last) { if (this.sound) this.beep(); }
                this.last = Math.max(this.last, now);
            }, 1500);
        },
    };
};
