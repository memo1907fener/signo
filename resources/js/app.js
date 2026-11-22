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
