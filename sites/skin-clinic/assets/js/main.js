/**
 * 白磁スキンクリニック（架空）: 全ページ共通のスクリプト
 * 依存なしの ES2020。各機能は対象の要素があるページでだけ動く。
 * トップページの演出は home.js、予約フォームは contact.js（そのページでだけ読み込む）。
 * JavaScript が動かなくても本文はすべて読める（段階的強化）。動きは prefers-reduced-motion に従う。
 */
'use strict';

(() => {
  const root = document.documentElement;
  const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const reduceMotion = () => motionQuery.matches;
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
  const create = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  const EASE = 'cubic-bezier(.2, .7, .2, 1)';
  const DAY = 86400000;
  const WEEK = ['日', '月', '火', '水', '木', '金', '土'];

  // 日本時間: Date の UTC 側の値を東京の時刻として使う（夏時間がないため +9時間で固定）
  // ?now=2026-10-06T18:10 を付けると、その時刻で表示する（受付状況の確認用）
  const pad = (n) => String(n).padStart(2, '0');
  const nowParam = new URLSearchParams(window.location.search).get('now');
  const tokyoNow = () => {
    const match = nowParam && nowParam.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
    if (match) {
      const [, y, m, d, hh, mm] = match.map(Number);
      return new Date(Date.UTC(y, m - 1, d, hh, mm));
    }
    return new Date(Date.now() + 9 * 3600000);
  };
  const ymd = (date) => `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;
  const addDays = (date, days) => new Date(date.getTime() + days * DAY);
  const toMinutes = (hhmm) => {
    const [h, m] = hhmm.split(':').map(Number);
    return h * 60 + m;
  };
  const fromMinutes = (minutes) => `${Math.floor(minutes / 60)}:${pad(minutes % 60)}`;
  const jaDate = (date, withWeekday = true) =>
    `${date.getUTCMonth() + 1}月${date.getUTCDate()}日${withWeekday ? `（${WEEK[date.getUTCDay()]}）` : ''}`;

  /** data-reception 属性の診療カレンダー（site.php の schedule / last_entry_minutes / closed_dates） */
  const readCalendar = (element) => {
    try {
      const data = JSON.parse(element.dataset.reception || '');
      return data && data.schedule ? data : null;
    } catch (error) {
      return null;
    }
  };
  const slotFor = (calendar, date) =>
    (calendar.closed || []).includes(ymd(date)) ? null : calendar.schedule[date.getUTCDay()] || null;
  const nextOpenDay = (calendar, from) => {
    for (let i = 1; i <= 31; i += 1) {
      const date = addDays(from, i);
      const slot = slotFor(calendar, date);
      if (slot) return { date, slot, tomorrow: i === 1 };
    }
    return null;
  };

  // 通知（role="status"）と、デモ版の tel: リンク（GUIDELINES 5章）
  const showToast = (() => {
    const element = $('.js-toast');
    let timer = 0;
    return (message) => {
      if (!element) return;
      element.textContent = message;
      element.classList.add('is-visible');
      window.clearTimeout(timer);
      timer = window.setTimeout(() => element.classList.remove('is-visible'), 4000);
    };
  })();

  if (root.dataset.build === 'demo') {
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href^="tel:"]');
      if (!link) return;
      event.preventDefault();
      showToast('サンプルサイトのため、電話はかかりません。');
    });
  }

  // ヘッダー: 少しスクロールしたらコンパクトにする（IntersectionObserver で監視）
  const initHeader = () => {
    const header = $('.js-header');
    const sentinel = $('.js-header-sentinel');
    if (!header || !sentinel || !('IntersectionObserver' in window)) return;
    new IntersectionObserver(([entry]) => {
      header.classList.toggle('is-compact', !entry.isIntersecting);
    }).observe(sentinel);
  };

  // ドロワー: JS がなければフッターのメニューへのリンクのまま。動けばボタンに置き換え、
  // showModal で背面を操作不可にし、Tab を中で循環させ、Esc で閉じてボタンへフォーカスを戻す
  const initDrawer = () => {
    const dialog = $('.js-drawer');
    const trigger = $('.js-drawer-toggle');
    if (!dialog || !trigger || typeof dialog.showModal !== 'function') return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = trigger.className;
    button.append(...trigger.childNodes);
    button.setAttribute('aria-controls', dialog.id);
    button.setAttribute('aria-expanded', 'false');
    trigger.replaceWith(button);

    const panel = $('.js-drawer-panel', dialog);
    let closing = false;
    const focusables = () =>
      $$('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])', panel);

    const open = () => {
      dialog.showModal();
      button.setAttribute('aria-expanded', 'true');
      if (!reduceMotion()) {
        panel.animate(
          [{ transform: 'translateX(32px)', opacity: 0 }, { transform: 'none', opacity: 1 }],
          { duration: 360, easing: EASE }
        );
      }
    };
    const close = () => {
      if (!dialog.open || closing) return;
      if (reduceMotion()) {
        dialog.close();
        return;
      }
      closing = true;
      const animation = panel.animate(
        [{ transform: 'none', opacity: 1 }, { transform: 'translateX(32px)', opacity: 0 }],
        { duration: 220, easing: 'ease-in', fill: 'forwards' }
      );
      animation.onfinish = () => {
        dialog.close();
        animation.cancel();
        closing = false;
      };
    };

    button.addEventListener('click', () => (dialog.open ? close() : open()));
    $('.js-drawer-close', dialog)?.addEventListener('click', close);
    dialog.addEventListener('cancel', (event) => {
      event.preventDefault();
      close();
    });
    dialog.addEventListener('close', () => {
      button.setAttribute('aria-expanded', 'false');
      button.focus();
    });
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) close();
    });
    dialog.addEventListener('keydown', (event) => {
      if (event.key !== 'Tab') return;
      const items = focusables();
      if (!items.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
    window.matchMedia('(min-width: 64em)').addEventListener('change', (event) => {
      if (event.matches && dialog.open) dialog.close();
    });
  };

  // ページ間の遷移（View Transitions）: 押した施術カードの画像だけに名前を付け、詳細ページの画像へつなぐ。
  // すべてのカードに常に名前を付けると、押していないカードの画像まで遷移先へ移動して見えるため
  const initViewTransition = () => {
    const clear = () => $$('.c-tx-card__visual').forEach((visual) => visual.style.removeProperty('view-transition-name'));
    document.addEventListener('click', (event) => {
      const link = event.target.closest('.c-tx-card__link');
      if (!link || reduceMotion()) return;
      clear();
      $('.p-tx-hero__visual')?.style.setProperty('view-transition-name', 'none');
      link.closest('.c-tx-card')?.querySelector('.c-tx-card__visual')?.style.setProperty('view-transition-name', 'tx-visual');
    });
    window.addEventListener('pageshow', (event) => {
      if (!event.persisted) return;
      clear();
      $('.p-tx-hero__visual')?.style.removeProperty('view-transition-name');
    });
  };

  // モバイルの予約バー: 予約導線（CTA）やフッターが見えている間は隠す
  const initSpBar = () => {
    const bar = $('.js-sp-bar');
    const targets = $$('.c-cta, .l-footer__inner');
    if (!bar || !targets.length || !('IntersectionObserver' in window)) return;
    const visible = new Set();
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
      bar.classList.toggle('is-hidden', visible.size > 0);
    });
    targets.forEach((target) => observer.observe(target));
  };

  // 悩み別の絞り込み（FLIP: 位置を記録 → 並べ替え → 差分を transform で戻して再生）
  // 外れるカードを先に薄くしてから外し、残るカードを新しい位置へ滑らせる
  const initFilters = () => {
    $$('.js-filter').forEach((filter) => {
      const list = document.getElementById(filter.dataset.filterTarget || '');
      if (!list) return;
      const chips = $$('[data-filter]', filter);
      const items = $$('.js-filter-item', list);
      const anchor = $('.js-filter-anchor', list);
      const status = $('.js-filter-status', filter);
      let token = 0;

      const apply = (key, label) => {
        const run = (token += 1);
        const matches = (item) => key === 'all' || item.dataset.categories.split(' ').includes(key);
        const shown = items.filter(matches);
        const leaving = items.filter((item) => !item.hidden && !matches(item));
        chips.forEach((chip) => chip.setAttribute('aria-pressed', String(chip.dataset.filter === key)));

        const settle = () => {
          if (run !== token) return;
          const movers = anchor ? [...items, anchor] : items;
          const first = new Map(movers.filter((el) => !el.hidden).map((el) => [el, el.getBoundingClientRect()]));
          movers.forEach((el) => el.getAnimations().forEach((animation) => animation.cancel()));

          [...shown, ...items.filter((item) => !matches(item))].forEach((item) => list.insertBefore(item, anchor));
          items.forEach((item) => {
            item.hidden = !matches(item);
          });

          if (!reduceMotion()) {
            [...shown, ...(anchor ? [anchor] : [])].forEach((el) => {
              const before = first.get(el);
              if (!before) {
                el.animate(
                  [{ opacity: 0, transform: 'translateY(16px)' }, { opacity: 1, transform: 'none' }],
                  { duration: 520, delay: 140, easing: EASE, fill: 'backwards' }
                );
                return;
              }
              const after = el.getBoundingClientRect();
              const dx = before.left - after.left;
              const dy = before.top - after.top;
              if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5) {
                el.animate(
                  [{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }],
                  { duration: 620, easing: EASE }
                );
              }
            });
          }
          if (status) {
            status.textContent = key === 'all'
              ? `すべての施術（${shown.length}件）を表示しています。`
              : `「${label}」に対応する施術を${shown.length}件表示しています。`;
          }
        };

        if (leaving.length && !reduceMotion()) {
          const fades = leaving.map((item) =>
            item.animate(
              [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(.97)' }],
              { duration: 200, easing: 'ease-in', fill: 'forwards' }
            )
          );
          Promise.all(fades.map((fade) => fade.finished.catch(() => null))).then(settle);
        } else {
          settle();
        }
      };

      chips.forEach((chip) => {
        chip.addEventListener('click', () => {
          if (chip.getAttribute('aria-pressed') === 'true') return;
          apply(chip.dataset.filter, chip.dataset.label);
        });
      });
    });
  };

  // タブ（WAI-ARIA のタブパターン）。JS がなければページ内リンクと全パネルがそのまま見える
  const initTabs = () => {
    $$('.js-tabs').forEach((container) => {
      const list = $('.js-tabs-list', container);
      const tabs = $$('a', list);
      const panels = tabs.map((tab) => document.getElementById(tab.hash.slice(1)));
      if (!tabs.length || panels.some((panel) => !panel)) return;

      list.setAttribute('role', 'tablist');
      tabs.forEach((tab, i) => {
        tab.parentElement.setAttribute('role', 'presentation');
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', panels[i].id);
        panels[i].setAttribute('role', 'tabpanel');
        panels[i].setAttribute('aria-labelledby', tab.id);
        panels[i].tabIndex = 0;
      });

      // 選んだタブが見えるよう、タブの列だけを横に動かす（ページは縦に動かさない）
      const reveal = (tab) => {
        const box = list.getBoundingClientRect();
        const rect = tab.getBoundingClientRect();
        if (rect.left < box.left) list.scrollLeft -= box.left - rect.left + 16;
        else if (rect.right > box.right) list.scrollLeft += rect.right - box.right + 16;
      };
      const select = (index, { focus = false, animate = true } = {}) => {
        tabs.forEach((tab, i) => {
          const selected = i === index;
          tab.setAttribute('aria-selected', String(selected));
          tab.tabIndex = selected ? 0 : -1;
          panels[i].hidden = !selected;
        });
        if (focus) tabs[index].focus();
        reveal(tabs[index]);
        if (animate && !reduceMotion()) {
          panels[index].animate([{ opacity: 0 }, { opacity: 1 }], { duration: 240, easing: EASE });
        }
      };
      const fromHash = () => panels.findIndex((panel) => `#${panel.id}` === window.location.hash);

      list.addEventListener('click', (event) => {
        const tab = event.target.closest('[role="tab"]');
        if (!tab) return;
        event.preventDefault();
        select(tabs.indexOf(tab));
        window.history.replaceState(null, '', tab.hash);
      });
      list.addEventListener('keydown', (event) => {
        const current = tabs.indexOf(document.activeElement);
        if (current < 0) return;
        const last = tabs.length - 1;
        const next = {
          ArrowRight: current === last ? 0 : current + 1,
          ArrowLeft: current === 0 ? last : current - 1,
          Home: 0,
          End: last,
        }[event.key];
        if (next === undefined) return;
        event.preventDefault();
        select(next, { focus: true });
        window.history.replaceState(null, '', tabs[next].hash);
      });
      window.addEventListener('hashchange', () => {
        const index = fromHash();
        if (index >= 0) select(index);
      });

      const initial = fromHash();
      container.classList.add('is-ready');
      select(Math.max(initial, 0), { animate: false });
      if (initial >= 0) list.scrollIntoView({ block: 'start', behavior: 'instant' });
    });
  };

  // アコーディオン（details）: 開閉の高さを Web Animations API で補間する。操作に応じた短い演出として
  // ここだけ例外的に height を動かす（動きを減らす設定ではブラウザ標準の開閉）
  const initAccordions = () => {
    $$('.js-accordion').forEach((details) => {
      const summary = $('summary', details);
      const body = $('.c-accordion__body', details);
      if (!summary || !body || typeof details.animate !== 'function') return;
      let animation = null;

      const play = (from, to, done) => {
        animation?.cancel();
        details.style.overflow = 'hidden';
        animation = details.animate([{ height: `${from}px` }, { height: `${to}px` }], { duration: 320, easing: EASE });
        animation.onfinish = () => {
          animation = null;
          details.style.overflow = '';
          done();
        };
        animation.oncancel = () => {
          details.style.overflow = '';
        };
      };

      summary.addEventListener('click', (event) => {
        if (reduceMotion()) return;
        event.preventDefault();
        const start = details.offsetHeight;
        const opening = !details.open || details.classList.contains('is-closing');
        if (opening) {
          details.classList.remove('is-closing');
          details.open = true;
          play(start, details.offsetHeight, () => {});
          body.animate(
            [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
            { duration: 360, easing: EASE }
          );
        } else {
          details.classList.add('is-closing');
          const borders = details.offsetHeight - details.clientHeight;
          play(start, summary.offsetHeight + borders, () => {
            details.open = false;
            details.classList.remove('is-closing');
          });
        }
      });
    });
  };

  // 診療時間の表: 日本時間の「本日」の行を強調する（祝日・休診日は「祝日」の行）
  const initHoursToday = () => {
    $$('.js-hours').forEach((table) => {
      const calendar = readCalendar(table);
      if (!calendar) return;
      const now = tokyoNow();
      const key = (calendar.closed || []).includes(ymd(now)) ? 'holiday' : String(now.getUTCDay());
      const row = $(`tr[data-day="${key}"]`, table);
      if (!row) return;
      row.classList.add('is-today');
      row.setAttribute('aria-current', 'date');
      $('th', row)?.append(create('span', 'c-hours__today', '本日'));
    });
  };

  // 施術詳細の目次: 読んでいる位置を示す
  const initToc = () => {
    const links = $$('.p-tx-toc__link');
    if (!links.length || !('IntersectionObserver' in window)) return;
    const targets = new Map();
    links.forEach((link) => {
      const target = document.getElementById(link.hash.slice(1));
      if (target) targets.set(target, link);
    });
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        links.forEach((link) => link.removeAttribute('aria-current'));
        targets.get(entry.target)?.setAttribute('aria-current', 'true');
      });
    }, { rootMargin: '-25% 0px -65% 0px' });
    targets.forEach((_, target) => observer.observe(target));
  };

  // ページ固有のスクリプト（home.js / contact.js）と共有する補助関数
  window.Hakuji = Object.freeze({
    root, motionQuery, reduceMotion, $, $$, create, EASE, WEEK,
    tokyoNow, ymd, addDays, toMinutes, fromMinutes, jaDate, readCalendar, slotFor, nextOpenDay, showToast,
  });

  initHeader();
  initDrawer();
  initViewTransition();
  initSpBar();
  initFilters();
  initTabs();
  initAccordions();
  initHoursToday();
  initToc();
})();
