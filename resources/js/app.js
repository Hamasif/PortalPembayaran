/* Portal Pembayaran Mahasiswa — utilitas UI ringan (vanilla JS) */

/* ------------------------------------------------------------------ */
/* Toast notification                                                  */
/* ------------------------------------------------------------------ */
window.toast = function (message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) {
        return;
    }

    const palette = {
        success: { icon: 'check-circle', bar: 'bg-brand-600', text: 'text-brand-700' },
        error: { icon: 'alert-circle', bar: 'bg-red-600', text: 'text-red-700' },
        warning: { icon: 'alert-triangle', bar: 'bg-accent-500', text: 'text-accent-700' },
        info: { icon: 'info', bar: 'bg-sky-600', text: 'text-sky-700' },
    };
    const style = palette[type] || palette.info;

    const el = document.createElement('div');
    el.className =
        'animate-fade-up pointer-events-auto flex w-full max-w-sm items-start gap-3 overflow-hidden rounded-xl border border-line bg-white shadow-lg';
    el.setAttribute('role', 'status');
    el.innerHTML =
        '<span class="w-1 self-stretch ' + style.bar + '"></span>' +
        '<div class="flex flex-1 items-start gap-2.5 px-3 py-3.5">' +
        '<span data-lucide="' + style.icon + '" class="mt-0.5 h-5 w-5 shrink-0 ' + style.text + '"></span>' +
        '<p class="flex-1 text-sm leading-snug text-ink"></p>' +
        '<button type="button" aria-label="Tutup notifikasi" class="mt-0.5 text-slate-400 transition hover:text-ink">' +
        '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
        '</button></div>';

    el.querySelector('p').textContent = message;

    const remove = () => {
        el.classList.add('opacity-0', 'transition-opacity', 'duration-200');
        setTimeout(() => el.remove(), 200);
    };
    el.querySelector('button').addEventListener('click', remove);
    container.appendChild(el);
    if (window.lucideIcons) {
        window.lucideIcons();
    }
    setTimeout(remove, duration);
};

/* Flash message dari session (dikonversi via data attribute) */
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('flash-data');
    if (flash) {
        const { type, message } = flash.dataset;
        if (message) {
            window.toast(message, type || 'success');
        }
    }
});

/* ------------------------------------------------------------------ */
/* Dropdown                                                            */
/* ------------------------------------------------------------------ */
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-dropdown-trigger]');
    if (trigger) {
        const wrapper = trigger.closest('[data-dropdown]');
        const menu = wrapper && wrapper.querySelector('[data-dropdown-menu]');
        if (menu) {
            menu.classList.toggle('hidden');
        }
        return;
    }
    document.querySelectorAll('[data-dropdown-menu]:not(.hidden)').forEach((menu) => {
        if (!menu.closest('[data-dropdown]').contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('[data-dropdown-menu]:not(.hidden)').forEach((m) => m.classList.add('hidden'));
        document.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal);
    }
});

/* ------------------------------------------------------------------ */
/* Modal                                                               */
/* ------------------------------------------------------------------ */
function openModal(modal) {
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    modal.dispatchEvent(new CustomEvent('modal:open'));
}

function closeModal(modal) {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-modal-open]');
    if (openTrigger) {
        const modal = document.getElementById(openTrigger.dataset.modalOpen);
        if (modal) {
            openModal(modal);
        }
        return;
    }
    const closeTrigger = event.target.closest('[data-modal-close]');
    if (closeTrigger) {
        const modal = closeTrigger.closest('[data-modal]');
        if (modal) {
            closeModal(modal);
        }
    }
});

/* ------------------------------------------------------------------ */
/* Sidebar mobile toggle (admin)                                       */
/* ------------------------------------------------------------------ */
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-sidebar-toggle]');
    const backdrop = event.target.closest('[data-sidebar-backdrop]');
    const sidebar = document.getElementById('admin-sidebar');
    if (sidebar && (toggle || backdrop)) {
        sidebar.classList.toggle('-translate-x-full');
        const bg = document.getElementById('sidebar-backdrop');
        if (bg) {
            bg.classList.toggle('hidden');
        }
    }
});

/* ------------------------------------------------------------------ */
/* Konfirmasi form (submit guard)                                      */
/* ------------------------------------------------------------------ */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

/* ------------------------------------------------------------------ */
/* Loading state untuk tombol submit                                   */
/* ------------------------------------------------------------------ */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.hasAttribute('data-loading')) {
        return;
    }
    form.setAttribute('data-loading', '');
    const button = form.querySelector('button[type="submit"]');
    if (button && !button.disabled) {
        button.disabled = true;
        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.innerHTML;
        }
        button.innerHTML =
            '<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path></svg>' +
            '<span>Memproses…</span>';
    }
});

/* ------------------------------------------------------------------ */
/* Countdown QRIS                                                      */
/* ------------------------------------------------------------------ */
function tickCountdown() {
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const deadline = new Date(el.dataset.countdown).getTime();
        const diff = deadline - Date.now();
        if (diff <= 0) {
            el.textContent = 'Kedaluwarsa';
            el.classList.add('text-red-600');
            if (!el.dataset.reloaded) {
                el.dataset.reloaded = '1';
                setTimeout(() => window.location.reload(), 1500);
            }
            return;
        }
        const minutes = String(Math.floor(diff / 60000)).padStart(2, '0');
        const seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
        el.textContent = minutes + ':' + seconds;
    });
}
document.addEventListener('DOMContentLoaded', () => {
    tickCountdown();
    setInterval(tickCountdown, 1000);
});

/* ------------------------------------------------------------------ */
/* Salin ke clipboard (mis. nomor Virtual Account)                     */
/* ------------------------------------------------------------------ */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-button]');
    if (!button) {
        return;
    }
    const source = button.closest('[data-copy], .card, form, div')?.querySelector('[data-copy]')
        || document.querySelector('[data-copy]');
    const text = source ? source.dataset.copy : '';
    if (!text) {
        return;
    }
    try {
        await navigator.clipboard.writeText(text);
        window.toast('Disalin: ' + text, 'success');
    } catch (error) {
        window.toast('Gagal menyalin ke clipboard.', 'error');
    }
});

/* ------------------------------------------------------------------ */
/* Grafik dashboard (Chart.js)                                         */
/* ------------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        if (!window.Chart) {
            return;
        }
        const payload = canvas.dataset.chart;
        if (!payload) {
            return;
        }
        let config;
        try {
            config = JSON.parse(payload);
        } catch (error) {
            console.error('Konfigurasi grafik tidak valid.', error);
            return;
        }
        config.options = config.options || {};
        new Chart(canvas, config);
    });
});
