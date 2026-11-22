// Passt eine TV-Seite automatisch in den Bildschirm ein (verkleinert Schrift bei viel Inhalt).
export function signoFit(root) {
    const pages = (root || document).querySelectorAll('.sg-tv');
    pages.forEach((page) => {
        const box = page.querySelector('.sg-fit');
        if (!box || page.classList.contains('sg-t-media')) return;
        // Ragt irgendein Element über den Innenrand (inkl. Abstand) hinaus?
        const overflows = () => {
            const r = box.getBoundingClientRect();
            const cs = getComputedStyle(box);
            const bottom = r.bottom - parseFloat(cs.paddingBottom) + 1;
            const right = r.right - parseFloat(cs.paddingRight) + 1;
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
}
window.signoFit = signoFit;

const refit = () => signoFit(document);
if (document.fonts && document.fonts.ready) document.fonts.ready.then(refit);
window.addEventListener('load', refit);
let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(refit, 150); });
