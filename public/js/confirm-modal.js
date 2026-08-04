/**
 * Custom confirm modal (replaces window.confirm).
 *
 * Forms:
 *   <form data-ui-confirm="Message" data-ui-confirm-title="Title" data-ui-confirm-tone="danger">
 *
 * Programmatic:
 *   UIConfirm.open({ message, title, tone, confirmLabel, onConfirm })
 */
(function () {
    'use strict';

    var modal = null;
    var pendingConfirm = null;
    var previousFocus = null;

    function el(sel, root) {
        return (root || document).querySelector(sel);
    }

    function ensureModal() {
        if (modal) return modal;
        modal = document.getElementById('ui-confirm-modal');
        return modal;
    }

    function setTone(tone) {
        var root = ensureModal();
        if (!root) return;
        root.classList.remove('ui-confirm-modal--danger', 'ui-confirm-modal--brand');
        root.classList.add(tone === 'brand' ? 'ui-confirm-modal--brand' : 'ui-confirm-modal--danger');
        var ok = el('[data-ui-confirm-ok]', root);
        if (ok) {
            ok.classList.toggle('ui-btn-danger', tone !== 'brand');
            ok.classList.toggle('ui-btn-primary', tone === 'brand');
        }
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function close() {
        var root = ensureModal();
        if (!root) return;
        root.classList.add('hidden');
        root.hidden = true;
        document.body.classList.remove('ui-confirm-open');
        pendingConfirm = null;
        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
        previousFocus = null;
    }

    function open(opts) {
        var root = ensureModal();
        if (!root) {
            if (window.confirm(opts.message || '')) {
                if (typeof opts.onConfirm === 'function') opts.onConfirm();
            }
            return;
        }

        previousFocus = document.activeElement;
        pendingConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;

        var title = opts.title || root.getAttribute('data-default-title') || '';
        var message = opts.message || '';
        var confirmLabel = opts.confirmLabel || root.getAttribute('data-default-ok') || 'OK';
        var cancelLabel = opts.cancelLabel || root.getAttribute('data-default-cancel') || 'Cancel';
        var tone = opts.tone === 'brand' ? 'brand' : 'danger';

        el('[data-ui-confirm-title]', root).textContent = title;
        el('[data-ui-confirm-message]', root).textContent = message;
        el('[data-ui-confirm-ok]', root).textContent = confirmLabel;
        el('[data-ui-confirm-cancel]', root).textContent = cancelLabel;
        setTone(tone);

        root.classList.remove('hidden');
        root.hidden = false;
        document.body.classList.add('ui-confirm-open');
        refreshIcons();
        el('[data-ui-confirm-ok]', root).focus();
    }

    function bindOnce() {
        var root = ensureModal();
        if (!root || root.dataset.bound === '1') return;
        root.dataset.bound = '1';

        el('[data-ui-confirm-cancel]', root).addEventListener('click', close);
        el('[data-ui-confirm-dismiss]', root).addEventListener('click', close);
        el('[data-ui-confirm-ok]', root).addEventListener('click', function () {
            var fn = pendingConfirm;
            close();
            if (fn) fn();
        });

        document.addEventListener('keydown', function (e) {
            if (!root || root.hidden) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
        });
    }

    function interceptFormSubmit(e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.uiConfirmSkip === '1') {
            delete form.dataset.uiConfirmSkip;
            return;
        }

        var message = form.getAttribute('data-ui-confirm');
        if (!message) return;

        e.preventDefault();
        e.stopPropagation();

        open({
            title: form.getAttribute('data-ui-confirm-title') || '',
            message: message,
            tone: form.getAttribute('data-ui-confirm-tone') || 'danger',
            confirmLabel: form.getAttribute('data-ui-confirm-ok') || undefined,
            cancelLabel: form.getAttribute('data-ui-confirm-cancel') || undefined,
            onConfirm: function () {
                form.dataset.uiConfirmSkip = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            },
        });
    }

    function boot() {
        bindOnce();
        document.addEventListener('submit', interceptFormSubmit, true);
    }

    window.UIConfirm = { open: open, close: close };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
