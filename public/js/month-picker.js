/**
 * Month/year picker (Flatpickr monthSelect) for billing Target Month fields.
 * Enhance: input[data-month-picker]
 */
(function () {
  'use strict';

  function localeCode() {
    var lang = (document.documentElement.lang || 'en').toLowerCase();
    return lang.indexOf('ja') === 0 ? 'ja' : null;
  }

  function initMonthPickers(root) {
    if (typeof flatpickr === 'undefined' || typeof monthSelectPlugin === 'undefined') {
      return;
    }

    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input[data-month-picker]').forEach(function (el) {
      if (el.dataset.monthPickerReady === '1') return;
      el.dataset.monthPickerReady = '1';

      var opts = {
        plugins: [
          new monthSelectPlugin({
            shorthand: true,
            dateFormat: 'Y-m',
            altFormat: 'Y-m',
          }),
        ],
        dateFormat: 'Y-m',
        altInput: true,
        altFormat: 'Y-m',
        allowInput: false,
        disableMobile: true,
        clickOpens: true,
        onChange: function () {
          if (el.dataset.autoSubmit === '1' && el.form) {
            el.form.submit();
          }
          el.dispatchEvent(new Event('change', { bubbles: true }));
        },
      };

      var loc = localeCode();
      if (loc && flatpickr.l10ns && flatpickr.l10ns[loc]) {
        opts.locale = flatpickr.l10ns[loc];
      }

      el._fp = flatpickr(el, opts);

      // Keep alt input styled like ui-input
      if (el._fp.altInput) {
        el._fp.altInput.className = (el.className || '') + ' ui-month-picker__display';
        el._fp.altInput.removeAttribute('readonly');
        el._fp.altInput.setAttribute('readonly', 'readonly');
      }
    });
  }

  function boot() {
    initMonthPickers(document);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.initMonthPickers = initMonthPickers;
})();
