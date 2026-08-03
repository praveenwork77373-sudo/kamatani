/**
 * Master code field: blur uniqueness check + empty = auto-generate note.
 */
(function () {
  'use strict';

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function resolveInstitutionId(wrap) {
    if (wrap.dataset.institutionId) return wrap.dataset.institutionId;
    const sel = wrap.dataset.institutionInput;
    if (!sel) return '';
    const el = document.querySelector(sel) || document.querySelector(`[name="${sel}"]`);
    return el ? el.value : '';
  }

  async function checkCode(wrap) {
    const input = wrap.querySelector('[data-master-code-input]');
    const status = wrap.querySelector('[data-master-code-status]');
    if (!input || !status) return;

    const code = (input.value || '').trim();
    const url = new URL(wrap.dataset.checkUrl, window.location.origin);
    url.searchParams.set('type', wrap.dataset.codeType);
    url.searchParams.set('code', code);
    if (wrap.dataset.excludeId) url.searchParams.set('exclude_id', wrap.dataset.excludeId);
    const institutionId = resolveInstitutionId(wrap);
    if (institutionId) url.searchParams.set('medical_institution_id', institutionId);

    status.classList.remove('hidden', 'text-emerald-600', 'text-amber-700', 'text-red-600');
    status.textContent = '…';

    try {
      const res = await fetch(url.toString(), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (!data.ok) {
        status.classList.add('text-red-600');
        status.textContent = data.message || 'Error';
        return;
      }
      if (data.empty) {
        status.classList.add('text-slate-500');
        status.textContent = data.message;
        input.classList.remove('border-red-400', 'border-emerald-400');
        return;
      }
      if (data.available) {
        status.classList.add('text-emerald-600');
        status.textContent = data.message;
        input.classList.remove('border-red-400');
        input.classList.add('border-emerald-400');
      } else {
        status.classList.add('text-amber-700');
        status.textContent = data.message;
        input.classList.remove('border-emerald-400');
        input.classList.add('border-red-400');
      }
    } catch (e) {
      status.classList.add('text-red-600');
      status.textContent = 'Check failed';
    }
  }

  function bind(wrap) {
    if (wrap.dataset.bound === '1') return;
    wrap.dataset.bound = '1';
    const input = wrap.querySelector('[data-master-code-input]');
    if (!input) return;

    let timer = null;
    const run = () => {
      clearTimeout(timer);
      timer = setTimeout(() => checkCode(wrap), 200);
    };

    input.addEventListener('blur', run);
    input.addEventListener('change', run);

    const instSel = wrap.dataset.institutionInput;
    if (instSel) {
      const el = document.querySelector(instSel) || document.querySelector(`[name="${instSel}"]`);
      if (el) el.addEventListener('change', () => {
        if ((input.value || '').trim()) run();
      });
    }
  }

  function boot(root) {
    (root || document).querySelectorAll('[data-master-code-wrap]').forEach(bind);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => boot());
  } else {
    boot();
  }

  window.refreshMasterCodeFields = boot;
})();
