/**
 * サンプル集: 説明の切り替えタブ（WAI-ARIA タブパターン）
 * JavaScript が動かない環境では、2つの説明を縦に並べて表示する。
 */
(() => {
  'use strict';

  const root = document.querySelector('.js-tabs');
  if (!root) return;

  const list = root.querySelector('[role="tablist"]');
  const tabs = [...root.querySelectorAll('[role="tab"]')];
  const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
  const storageKey = 'sample-hub-tab';

  panels.forEach((panel, i) => {
    panel.setAttribute('role', 'tabpanel');
    panel.setAttribute('aria-labelledby', tabs[i].id);
    panel.tabIndex = 0;
  });
  list.hidden = false;
  root.classList.add('is-ready');

  const select = (index, { focus = false, remember = true } = {}) => {
    tabs.forEach((tab, i) => {
      const selected = i === index;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      panels[i].hidden = !selected;
    });
    if (focus) tabs[index].focus();
    if (remember) {
      try {
        localStorage.setItem(storageKey, panels[index].id);
      } catch {
        // 保存できない環境では記憶しない
      }
    }
  };

  tabs.forEach((tab, i) => {
    tab.addEventListener('click', () => select(i));
    tab.addEventListener('keydown', (event) => {
      const last = tabs.length - 1;
      const next = { ArrowRight: i === last ? 0 : i + 1, ArrowLeft: i === 0 ? last : i - 1, Home: 0, End: last }[event.key];
      if (next === undefined) return;
      event.preventDefault();
      select(next, { focus: true });
    });
  });

  // ページ内リンク（#for-studios など）で開いたときはその説明を表示する
  const fromHash = panels.findIndex((panel) => `#${panel.id}` === location.hash);
  let initial = fromHash;
  if (initial < 0) {
    try {
      initial = panels.findIndex((panel) => panel.id === localStorage.getItem(storageKey));
    } catch {
      initial = -1;
    }
  }
  select(Math.max(initial, 0), { remember: false });
})();
