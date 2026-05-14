import './bootstrap';

import Alpine from 'alpinejs';
import * as lucideIcons from 'lucide';

// The npm `lucide` package exports each icon as a PascalCase constant —
// e.g. `KeyRound` for `data-lucide="key-round"`. Each icon is an array of
// `[tag, attrs, children]` tuples that we render into inline SVG. This is
// a 30-line replacement for what the CDN's `lucide.createIcons()` did.
const SVG_NS = 'http://www.w3.org/2000/svg';

function kebabToPascal(name) {
    return name.split('-').map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join('');
}

function buildSvgChild([tag, attrs, children]) {
    const el = document.createElementNS(SVG_NS, tag);
    if (attrs) {
        Object.keys(attrs).forEach((k) => el.setAttribute(k, String(attrs[k])));
    }
    if (children && children.length) {
        children.forEach((c) => el.appendChild(buildSvgChild(c)));
    }
    return el;
}

function renderLucideIcon(el) {
    const name = el.getAttribute('data-lucide');
    if (!name) return;
    const iconData = lucideIcons[kebabToPascal(name)];
    if (!iconData) return; // unknown icon — leave the placeholder, don't crash

    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('xmlns', SVG_NS);
    svg.setAttribute('width', '24');
    svg.setAttribute('height', '24');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', el.getAttribute('data-stroke-width') || '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');

    // Copy every attribute from the <i> over to the <svg> so Alpine directives
    // (x-show, x-cloak, :class, etc.) keep working after the replacement.
    // Skip our internal data-lucide / data-stroke-width markers.
    for (const attr of Array.from(el.attributes)) {
        if (attr.name === 'data-lucide' || attr.name === 'data-stroke-width') continue;
        svg.setAttribute(attr.name, attr.value);
    }

    iconData.forEach((tuple) => svg.appendChild(buildSvgChild(tuple)));
    el.replaceWith(svg);
}

window.lucide = {
    createIcons() {
        document.querySelectorAll('i[data-lucide]').forEach(renderLucideIcon);
    },
};

// ---------------------------------------------------------------------------
// Alpine global stores
// ---------------------------------------------------------------------------
document.addEventListener('alpine:init', () => {
    // Theme store — persists to localStorage and toggles the `dark` class on <html>.
    Alpine.store('theme', {
        current: localStorage.getItem('vault-theme') || 'dark',
        toggle() {
            this.current = this.current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('vault-theme', this.current);
            if (this.current === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
    });

    // Sidebar store — mobile open/close.
    Alpine.store('sidebar', {
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    });
});

// IMPORTANT: render Lucide icons BEFORE Alpine.start() so the resulting
// <svg> elements (with x-show / x-cloak / :class copied over) are what
// Alpine scans. If we replaced <i> → <svg> after Alpine had already bound
// to the <i>, the directives on the new <svg> would never fire.
function renderIcons() {
    if (window.lucide?.createIcons) {
        window.lucide.createIcons();
    }
}
renderIcons();

window.Alpine = Alpine;
Alpine.start();

// MutationObserver: any new <i data-lucide> added later (toasts, modals) is rendered.
const observer = new MutationObserver((mutations) => {
    let shouldRender = false;
    mutations.forEach((m) => {
        m.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return;
            if (node.matches?.('[data-lucide]') || node.querySelector?.('[data-lucide]')) {
                shouldRender = true;
            }
        });
    });
    if (shouldRender) {
        renderIcons();
    }
});
observer.observe(document.body, { childList: true, subtree: true });

// ---------------------------------------------------------------------------
// Clipboard helper with auto-clear
// ---------------------------------------------------------------------------
window.copyToClipboard = async function (text, { clearAfter = null, label = null } = {}) {
    try {
        await navigator.clipboard.writeText(text);
        window.dispatchEvent(new CustomEvent('toast', {
            detail: {
                type: 'success',
                message: `${label || 'Copied'} to clipboard${clearAfter ? ` · clears in ${clearAfter}s` : ''}`,
            },
        }));

        if (clearAfter) {
            setTimeout(async () => {
                try {
                    const current = await navigator.clipboard.readText();
                    if (current === text) {
                        await navigator.clipboard.writeText('');
                    }
                } catch (_) { /* clipboard may be inaccessible after focus loss */ }
            }, clearAfter * 1000);
        }
    } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'error', message: 'Could not copy to clipboard' },
        }));
    }
};

// ---------------------------------------------------------------------------
// Copy a credential field via the server (audits the access on the backend)
// ---------------------------------------------------------------------------
window.copyCredentialField = async function (el) {
    const id = el.dataset.credentialId;
    const field = el.dataset.field;
    const label = el.dataset.label || field;
    const meta = JSON.parse(document.querySelector('meta[name=vault-config]')?.content || '{}');

    try {
        const res = await fetch(`/credentials/${id}/copy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': meta.csrfToken,
            },
            body: JSON.stringify({ field }),
        });
        if (!res.ok) throw new Error('Copy failed');
        const data = await res.json();
        window.copyToClipboard(data.value, { clearAfter: data.clear_after, label });
    } catch (_) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Copy failed' } }));
    }
};

// ---------------------------------------------------------------------------
// Idle-timeout auto-logout
// ---------------------------------------------------------------------------
(function setupIdleTimeout() {
    const config = (() => {
        try {
            return JSON.parse(document.querySelector('meta[name=vault-config]')?.content || '{}');
        } catch (_) { return {}; }
    })();

    if (!config.idleTimeoutSeconds || !config.logoutUrl || !config.csrfToken) return;

    let timer;
    const timeoutMs = config.idleTimeoutSeconds * 1000;

    function logout() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = config.logoutUrl;
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = config.csrfToken;
        form.appendChild(token);
        const reason = document.createElement('input');
        reason.type = 'hidden';
        reason.name = 'reason';
        reason.value = 'idle';
        form.appendChild(reason);
        document.body.appendChild(form);
        form.submit();
    }

    function reset() {
        clearTimeout(timer);
        timer = setTimeout(logout, timeoutMs);
    }

    ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach((evt) =>
        document.addEventListener(evt, reset, { passive: true })
    );
    reset();
})();
