/**
 * Global custom select — replaces native <select> UI across the app.
 * Keeps the original <select> in the DOM for forms / validation / change handlers.
 *
 * Skip: multiple, [data-native-select], already enhanced.
 * Refresh after dynamic option changes: window.refreshCustomSelects(root?)
 */
(function () {
  'use strict';

  const SEARCH_THRESHOLD = 8;
  const instances = new WeakMap();

  function iconChevron() {
    return (
      '<svg class="ui-cselect-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>'
    );
  }

  function iconCheck() {
    return (
      '<svg class="ui-cselect-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>'
    );
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  class CustomSelect {
    constructor(select) {
      this.select = select;
      this.isOpen = false;
      this.build();
      this.bind();
      this.syncFromSelect();
      instances.set(select, this);
      select.dataset.cselect = '1';
    }

    build() {
      const select = this.select;
      const wrap = document.createElement('div');
      wrap.className = 'ui-cselect';
      if (select.disabled) wrap.classList.add('is-disabled');
      if (select.classList.contains('ui-select') || select.className.includes('text-[13px]')) {
        wrap.classList.add('ui-cselect--compact');
      }

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'ui-cselect-trigger';
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');
      if (select.id) trigger.id = select.id + '-cselect-trigger';
      if (select.disabled) trigger.disabled = true;

      const label = document.createElement('span');
      label.className = 'ui-cselect-label';
      trigger.appendChild(label);
      trigger.insertAdjacentHTML('beforeend', iconChevron());

      const panel = document.createElement('div');
      panel.className = 'ui-cselect-panel';
      panel.hidden = true;

      const searchWrap = document.createElement('div');
      searchWrap.className = 'ui-cselect-search-wrap';
      const search = document.createElement('input');
      search.type = 'search';
      search.className = 'ui-cselect-search';
      search.placeholder = '検索…';
      search.setAttribute('autocomplete', 'off');
      searchWrap.appendChild(search);

      const list = document.createElement('ul');
      list.className = 'ui-cselect-list';
      list.setAttribute('role', 'listbox');

      panel.appendChild(searchWrap);
      panel.appendChild(list);

      const parent = select.parentNode;
      parent.insertBefore(wrap, select);
      wrap.appendChild(trigger);
      // Panel lives on <body> when open so overflow:hidden parents cannot clip it
      wrap.appendChild(select);

      select.classList.add('ui-cselect-native');
      select.style.backgroundImage = 'none';

      this.wrap = wrap;
      this.trigger = trigger;
      this.labelEl = label;
      this.panel = panel;
      this.searchWrap = searchWrap;
      this.search = search;
      this.list = list;
      this._onReposition = () => {
        if (this.isOpen) this.positionPanel();
      };

      this.rebuildOptions();
    }

    optionNodes() {
      return Array.from(this.select.querySelectorAll('option'));
    }

    rebuildOptions() {
      const options = this.optionNodes();
      const showSearch = options.length >= SEARCH_THRESHOLD;
      this.searchWrap.hidden = !showSearch;
      this.list.innerHTML = '';

      options.forEach((opt, index) => {
        const li = document.createElement('li');
        li.className = 'ui-cselect-option';
        li.setAttribute('role', 'option');
        li.dataset.value = opt.value;
        li.dataset.index = String(index);
        if (opt.disabled) {
          li.classList.add('is-disabled');
          li.setAttribute('aria-disabled', 'true');
        }
        if (opt.value === '') {
          li.classList.add('is-placeholder');
        }

        const text = document.createElement('span');
        text.className = 'ui-cselect-option-text';
        text.textContent = opt.textContent.trim() || '\u00A0';
        li.appendChild(text);
        li.insertAdjacentHTML('beforeend', iconCheck());

        this.list.appendChild(li);
      });

      this.syncFromSelect();
    }

    syncFromSelect() {
      const select = this.select;
      const selected = select.options[select.selectedIndex];
      const text = selected ? selected.textContent.trim() : '';
      const isPlaceholder = !selected || selected.value === '';

      this.labelEl.textContent = text || '選択してください';
      this.labelEl.classList.toggle('is-placeholder', isPlaceholder);
      this.wrap.classList.toggle('is-disabled', select.disabled);
      this.trigger.disabled = select.disabled;

      this.list.querySelectorAll('.ui-cselect-option').forEach((li) => {
        const selectedMatch = Number(li.dataset.index) === select.selectedIndex;
        li.classList.toggle('is-selected', selectedMatch);
        li.setAttribute('aria-selected', selectedMatch ? 'true' : 'false');
      });
    }

    open() {
      if (this.select.disabled) return;
      closeAll(this);
      this.isOpen = true;
      this.wrap.classList.add('is-open');
      this.panel.classList.add('is-portal');
      this.panel.hidden = false;
      if (this.panel.parentNode !== document.body) {
        document.body.appendChild(this.panel);
      }
      this.trigger.setAttribute('aria-expanded', 'true');
      this.search.value = '';
      this.filter('');
      this.positionPanel();
      window.addEventListener('scroll', this._onReposition, true);
      window.addEventListener('resize', this._onReposition);
      if (!this.searchWrap.hidden) {
        requestAnimationFrame(() => this.search.focus());
      }
      const selected = this.list.querySelector('.is-selected');
      if (selected) selected.scrollIntoView({ block: 'nearest' });
    }

    close() {
      if (!this.isOpen) return;
      this.isOpen = false;
      this.wrap.classList.remove('is-open');
      this.panel.hidden = true;
      this.panel.classList.remove('is-portal');
      this.trigger.setAttribute('aria-expanded', 'false');
      window.removeEventListener('scroll', this._onReposition, true);
      window.removeEventListener('resize', this._onReposition);
      // Keep panel on body but hidden (avoids re-append churn)
    }

    toggle() {
      this.isOpen ? this.close() : this.open();
    }

    positionPanel() {
      const rect = this.trigger.getBoundingClientRect();
      const gap = 6;
      const width = Math.max(rect.width, 160);
      const maxH = Math.min(288, window.innerHeight * 0.7);
      // Temporarily show to measure
      this.panel.style.visibility = 'hidden';
      this.panel.style.display = 'flex';
      this.panel.style.position = 'fixed';
      this.panel.style.width = width + 'px';
      this.panel.style.maxHeight = maxH + 'px';
      this.panel.style.left = Math.min(rect.left, window.innerWidth - width - 8) + 'px';
      this.panel.style.zIndex = '10050';

      const panelH = Math.min(maxH, this.panel.scrollHeight || maxH);
      const spaceBelow = window.innerHeight - rect.bottom - gap;
      const spaceAbove = rect.top - gap;
      let top;
      if (spaceBelow < panelH && spaceAbove > spaceBelow) {
        top = Math.max(8, rect.top - gap - panelH);
      } else {
        top = Math.min(rect.bottom + gap, window.innerHeight - Math.min(panelH, maxH) - 8);
      }
      this.panel.style.top = top + 'px';
      this.panel.style.bottom = 'auto';
      this.panel.style.visibility = '';
    }

    filter(query) {
      const q = query.trim().toLowerCase();
      let visible = 0;
      this.list.querySelectorAll('.ui-cselect-option').forEach((li) => {
        const text = (li.textContent || '').toLowerCase();
        const show = !q || text.includes(q);
        li.hidden = !show;
        if (show) visible += 1;
      });
      let empty = this.list.querySelector('.ui-cselect-empty');
      if (visible === 0) {
        if (!empty) {
          empty = document.createElement('li');
          empty.className = 'ui-cselect-empty';
          empty.textContent = '該当なし';
          this.list.appendChild(empty);
        }
        empty.hidden = false;
      } else if (empty) {
        empty.hidden = true;
      }
    }

    choose(li) {
      if (!li || li.classList.contains('is-disabled') || li.classList.contains('ui-cselect-empty')) return;
      const index = Number(li.dataset.index);
      if (Number.isNaN(index)) return;
      this.select.selectedIndex = index;
      this.select.dispatchEvent(new Event('input', { bubbles: true }));
      this.select.dispatchEvent(new Event('change', { bubbles: true }));
      this.syncFromSelect();
      this.close();
      this.trigger.focus();
    }

    bind() {
      this.trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.toggle();
      });

      this.trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
          e.preventDefault();
          this.open();
        } else if (e.key === 'Escape') {
          this.close();
        }
      });

      this.list.addEventListener('click', (e) => {
        const li = e.target.closest('.ui-cselect-option');
        if (li) this.choose(li);
      });

      this.search.addEventListener('input', () => this.filter(this.search.value));
      this.search.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          e.stopPropagation();
          this.close();
          this.trigger.focus();
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          const first = this.list.querySelector('.ui-cselect-option:not([hidden]):not(.is-disabled)');
          if (first) first.focus?.();
        }
      });

      this.select.addEventListener('change', () => this.syncFromSelect());
      this.select.addEventListener('cselect:sync', () => this.syncFromSelect());
      this.select.addEventListener('focus', () => {
        this.trigger.focus();
      });

      this.optionsObserver = new MutationObserver(() => {
        this.rebuildOptions();
      });
      this.optionsObserver.observe(this.select, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['disabled'],
      });
    }

    destroy() {
      this.optionsObserver?.disconnect();
      window.removeEventListener('scroll', this._onReposition, true);
      window.removeEventListener('resize', this._onReposition);
      if (this.panel && this.panel.parentNode) {
        this.panel.parentNode.removeChild(this.panel);
      }
      const parent = this.wrap.parentNode;
      if (parent) {
        parent.insertBefore(this.select, this.wrap);
        this.select.classList.remove('ui-cselect-native');
        this.select.style.backgroundImage = '';
        delete this.select.dataset.cselect;
        parent.removeChild(this.wrap);
      }
      instances.delete(this.select);
    }
  }

  function closeAll(except) {
    document.querySelectorAll('.ui-cselect.is-open').forEach((el) => {
      const select = el.querySelector('select.ui-cselect-native');
      const inst = select && instances.get(select);
      if (inst && inst !== except) inst.close();
    });
  }

  function shouldEnhance(select) {
    if (!(select instanceof HTMLSelectElement)) return false;
    if (select.multiple) return false;
    if (select.dataset.nativeSelect !== undefined) return false;
    if (select.dataset.cselect === '1') return false;
    if (select.closest('.ui-cselect')) return false;
    return true;
  }

  function enhance(select) {
    if (!shouldEnhance(select)) return instances.get(select) || null;
    return new CustomSelect(select);
  }

  function enhanceAll(root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('select').forEach((select) => enhance(select));
  }

  function refresh(root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('select[data-cselect="1"]').forEach((select) => {
      const inst = instances.get(select);
      if (inst) inst.rebuildOptions();
    });
    enhanceAll(scope);
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('.ui-cselect') || e.target.closest('.ui-cselect-panel')) return;
    closeAll();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });

  // Enhance dynamically inserted selects (product forms, etc.)
  const bodyObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) return;
        if (node.matches?.('select')) enhance(node);
        else if (node.querySelectorAll) enhanceAll(node);
      });
    }
  });

  function boot() {
    enhanceAll(document);
    if (document.body) {
      bodyObserver.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.CustomSelect = { enhance, enhanceAll, refresh, get: (el) => instances.get(el) };
  window.refreshCustomSelects = refresh;
})();
