/**
 * 白磁スキンクリニック（架空）
 * 依存なしの ES2020。各機能は対象の要素があるページでだけ動く。
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

  // 本日の受付状況（SVG のリング）と、診療時間の表の「本日」の強調
  const initReception = () => {
    const widget = $('.js-reception');
    const calendar = widget && readCalendar(widget);
    if (!calendar) return;
    const part = (name) => $(`.js-reception-${name}`, widget);
    const bar = part('bar');

    const render = () => {
      const now = tokyoNow();
      const minutes = now.getUTCHours() * 60 + now.getUTCMinutes();
      const slot = slotFor(calendar, now);
      const view = { ratio: 0 };
      const nextText = () => {
        const next = nextOpenDay(calendar, now);
        if (!next) return { pre: '次回の診療', value: '—', unit: '', text: '' };
        const label = `${next.tomorrow ? '明日・' : ''}${jaDate(next.date)}`;
        return {
          pre: '次回の診療',
          value: `${next.date.getUTCMonth() + 1}/${next.date.getUTCDate()}`,
          unit: `（${WEEK[next.date.getUTCDay()]}）${next.slot.open}〜`,
          text: `次回の診療は${label}の${next.slot.open}からです。Web予約は24時間受け付けています。`,
        };
      };

      if (!slot) {
        const next = nextText();
        Object.assign(view, { state: 'closed', title: '本日休診', detail: next.text, pre: next.pre, value: next.value, unit: next.unit });
      } else {
        const open = toMinutes(slot.open);
        const last = toMinutes(slot.close) - Number(calendar.lastEntry || 0);
        if (minutes < open) {
          Object.assign(view, {
            state: 'before',
            title: '診療開始前',
            detail: `本日は${slot.open}から診療します。最終受付は${fromMinutes(last)}です。`,
            pre: '本日の診療',
            value: slot.open,
            unit: 'から',
            ratio: 1,
          });
        } else if (minutes < last) {
          const remain = last - minutes;
          const closing = remain <= 60;
          Object.assign(view, {
            state: closing ? 'closing' : 'open',
            title: closing ? 'まもなく受付終了' : '診療中',
            detail: `本日の受付終了まで${remain}分です（最終受付 ${fromMinutes(last)}）。${closing ? 'お急ぎの方はお電話ください。' : ''}`,
            pre: '受付終了まで',
            value: String(remain),
            unit: '分',
            ratio: remain / (last - open),
          });
        } else {
          const next = nextText();
          Object.assign(view, { state: 'after', title: '本日の受付は終了しました', detail: next.text, pre: next.pre, value: next.value, unit: next.unit });
        }
      }

      widget.dataset.state = view.state;
      bar.setAttribute('stroke-dasharray', `${(Math.min(Math.max(view.ratio, 0), 1) * 100).toFixed(2)} 100`);
      part('label').textContent = '本日の受付状況';
      part('state').textContent = view.title;
      part('detail').textContent = view.detail;
      part('pre').textContent = view.pre;
      part('value').textContent = view.value;
      part('unit').textContent = view.unit;
    };

    render();
    const tick = () => {
      window.setTimeout(() => {
        render();
        tick();
      }, 60000 - (Date.now() % 60000) + 50);
    };
    tick();
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) render();
    });
  };

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

  // ファーストビューの「釉薬のゆらぎ」: 白磁の画像の上で呉須と青磁色のもやをゆっくり動かす。
  // 30fps 以下。画面外・タブ非表示では停止し、動きを減らす設定では描画しない（画像だけで完成）
  const initGlaze = () => {
    const canvas = $('.js-glaze');
    const toggle = $('.js-glaze-toggle');
    const context = canvas && canvas.getContext ? canvas.getContext('2d') : null;
    if (!context) return;

    const css = getComputedStyle(root);
    const rgb = (name) => {
      const value = parseInt(css.getPropertyValue(name).trim().replace('#', ''), 16) || 0;
      return [(value >> 16) & 255, (value >> 8) & 255, value & 255];
    };
    const mix = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
    const cobalt = rgb('--color-cobalt');
    const celadon = mix(rgb('--color-celadon'), rgb('--color-ink'), 0.22);
    const mist = mix(rgb('--color-mist'), cobalt, 0.18);
    const porcelain = rgb('--color-porcelain');
    // [色, 濃さ, 中心x, 中心y, 半径, 縦横比, 傾き, 揺れ幅x, 揺れ幅y, 周期（秒）, 位相]
    const veils = [
      [mist, 0.5, 0.74, 0.18, 0.46, 0.42, 0.25, 0.05, 0.04, 61, 0.0],
      [cobalt, 0.12, 0.8, 0.36, 0.38, 0.5, -0.4, 0.06, 0.05, 47, 1.7],
      [celadon, 0.3, 0.9, 0.82, 0.5, 0.55, 0.35, 0.04, 0.05, 73, 3.1],
      [cobalt, 0.06, 0.56, 0.7, 0.32, 0.46, -0.9, 0.07, 0.04, 53, 4.4],
      [porcelain, 0.55, 0.24, 0.6, 0.42, 0.7, 0.1, 0.04, 0.03, 67, 2.2],
    ];

    let width = 0;
    let height = 0;
    const resize = () => {
      const rect = canvas.getBoundingClientRect();
      const scale = Math.min(window.devicePixelRatio || 1, 2) * 0.5; // もやは低解像度で描いて拡大しても粗が出ない
      width = Math.max(1, Math.round(rect.width * scale));
      height = Math.max(1, Math.round(rect.height * scale));
      canvas.width = width;
      canvas.height = height;
    };
    const draw = (seconds) => {
      context.clearRect(0, 0, width, height);
      const size = Math.max(width, height);
      veils.forEach(([color, alpha, x, y, radius, ratio, tilt, ax, ay, period, phase]) => {
        const angle = (seconds / period) * Math.PI * 2 + phase;
        const cx = (x + Math.sin(angle) * ax) * width;
        const cy = (y + Math.cos(angle * 0.8) * ay) * height;
        const r = radius * size * (1 + Math.sin(angle * 1.3) * 0.05);
        const gradient = context.createRadialGradient(0, 0, 0, 0, 0, r);
        const [red, green, blue] = color;
        gradient.addColorStop(0, `rgba(${red}, ${green}, ${blue}, ${alpha})`);
        gradient.addColorStop(0.55, `rgba(${red}, ${green}, ${blue}, ${alpha * 0.45})`);
        gradient.addColorStop(1, `rgba(${red}, ${green}, ${blue}, 0)`);
        context.save();
        context.translate(cx, cy);
        context.rotate(tilt + Math.sin(angle * 0.6) * 0.12);
        context.scale(1, ratio);
        context.fillStyle = gradient;
        context.beginPath();
        context.arc(0, 0, r, 0, Math.PI * 2);
        context.fill();
        context.restore();
      });
    };

    const FRAME = 1000 / 30;
    const startedAt = performance.now();
    let frame = 0;
    let lastDraw = 0;
    let running = false;
    let inView = true;
    let paused = false;
    try {
      paused = window.localStorage.getItem('hakuji-glaze') === 'paused';
    } catch (error) {
      paused = false;
    }

    const loop = (now) => {
      frame = window.requestAnimationFrame(loop);
      if (now - lastDraw < FRAME) return;
      lastDraw = now;
      draw((now - startedAt) / 1000);
    };
    const start = () => {
      if (running || paused || !inView || document.hidden || reduceMotion()) return;
      running = true;
      frame = window.requestAnimationFrame(loop);
    };
    const stop = () => {
      running = false;
      window.cancelAnimationFrame(frame);
    };
    const enable = () => {
      if (reduceMotion()) {
        stop();
        canvas.classList.remove('is-running');
        if (toggle) toggle.hidden = true;
        return;
      }
      resize();
      draw((performance.now() - startedAt) / 1000);
      canvas.classList.add('is-running');
      if (toggle) {
        toggle.hidden = false;
        toggle.setAttribute('aria-pressed', String(paused));
      }
      start();
    };

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(([entry]) => {
        inView = entry.isIntersecting;
        if (inView) start();
        else stop();
      }).observe(canvas);
    }
    if ('ResizeObserver' in window) {
      new ResizeObserver(() => {
        if (!canvas.classList.contains('is-running')) return;
        resize();
        draw((performance.now() - startedAt) / 1000);
      }).observe(canvas);
    }
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    motionQuery.addEventListener('change', enable);
    toggle?.addEventListener('click', () => {
      paused = !paused;
      toggle.setAttribute('aria-pressed', String(paused));
      try {
        window.localStorage.setItem('hakuji-glaze', paused ? 'paused' : 'playing');
      } catch (error) {
        /* 保存できない環境では、このページを開いている間だけ有効 */
      }
      if (paused) stop();
      else start();
    });
    enable();
  };

  // 予約フォーム: サーバー版の送信は PHP（FormFlow）。ここでは日付の範囲・休診日の注意・文字数・二重送信の防止。
  // 静的版は送信せず、入力 → 確認 → 完了の流れを画面上で再現する（入力値は textContent で表示）
  const initForm = () => {
    $('.js-focus-on-load')?.focus();

    $$('form[method="post"]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (form.hasAttribute('data-demo')) return;
        if (form.dataset.submitting) {
          event.preventDefault();
          return;
        }
        form.dataset.submitting = 'true';
      });
    });
    window.addEventListener('pageshow', () => {
      $$('form[data-submitting]').forEach((form) => delete form.dataset.submitting);
    });

    const form = $('.js-contact-form');
    if (!form) return;
    const calendar = readCalendar(form);
    const today = tokyoNow();
    const minDate = ymd(addDays(today, 1));
    const maxDate = ymd(addDays(today, 60));

    // 日付の範囲（静的版は HTML に書けないためここで設定）と休診日の注意
    $$('.js-date', form).forEach((input) => {
      if (!input.min) input.min = minDate;
      if (!input.max) input.max = maxDate;
      const warn = () => {
        const field = input.closest('.c-field');
        field.querySelector('.js-closed-warning')?.remove();
        if (!calendar || !/^\d{4}-\d{2}-\d{2}$/.test(input.value)) return;
        const [y, m, d] = input.value.split('-').map(Number);
        if (slotFor(calendar, new Date(Date.UTC(y, m - 1, d)))) return;
        input.after(create('p', 'c-field__warning js-closed-warning', '選択された日は休診日です。この日をご希望の場合は、近い日程をご提案します。'));
      };
      input.addEventListener('change', warn);
      warn();
    });

    // 施術ページからのリンク（?menu=ipl）では、気になる施術を選んだ状態にする
    const select = $('#f-menu', form);
    const preset = new URLSearchParams(window.location.search).get('menu');
    if (select && preset && !select.value && Array.from(select.options).some((option) => option.value === preset)) {
      select.value = preset;
    }

    // 「その他のお問い合わせ」では希望日と時間帯を任意にする（サーバー側の判定と同じ）
    const purposeInputs = $$('input[name="purpose"]', form);
    const syncPurpose = () => {
      const inquiry = purposeInputs.some((input) => input.checked && input.value === 'other');
      $$('.js-required-badge', form).forEach((holder) => {
        const badge = holder.querySelector('.c-field__badge');
        if (!badge) return;
        badge.textContent = inquiry ? '任意' : '必須';
        badge.classList.toggle('c-field__badge--required', !inquiry);
      });
      const date1 = $('#f-date1', form);
      if (date1) date1.required = !inquiry;
      $$('input[name="time"]', form).forEach((input) => {
        input.required = !inquiry;
      });
      $('#f-time', form)?.setAttribute('aria-required', String(!inquiry));
    };
    purposeInputs.forEach((input) => input.addEventListener('change', syncPurpose));
    syncPurpose();

    // ご相談内容の文字数
    $$('.js-counter', form).forEach((textarea) => {
      const max = Number(textarea.getAttribute('maxlength')) || 1000;
      const counter = create('p', 'c-field__counter');
      counter.setAttribute('aria-hidden', 'true');
      const update = () => {
        counter.textContent = `${textarea.value.length} / ${max}`;
      };
      textarea.after(counter);
      textarea.addEventListener('input', update);
      update();
    });

    if (form.hasAttribute('data-demo')) initDemoForm(form, { minDate, maxDate });
  };

  /* 静的版の送信デモ（サーバーの検証 core/form/Validator.php と同じ規則・同じ文言） */
  const initDemoForm = (form, { minDate, maxDate }) => {
    const stage = $('.js-form-stage');
    const steps = $$('.js-steps > li');
    const submit = $('.js-submit', form);
    if (!stage || !submit) return;
    submit.disabled = false;

    const labels = {
      name: 'お名前',
      kana: 'フリガナ',
      email: 'メールアドレス',
      tel: '電話番号',
      purpose: 'ご用件',
      menu: '気になる施術',
      date1: '第1希望日',
      time: '時間帯',
      date2: '第2希望日',
      message: 'ご相談内容',
      consent: '個人情報の取り扱い',
    };
    const toHalfWidth = (value) =>
      value.replace(/[\uff01-\uff5e]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0xfee0)).replace(/\u3000/g, ' ');
    const toKatakana = (value) => value.replace(/[ぁ-ゖ]/g, (c) => String.fromCharCode(c.charCodeAt(0) + 0x60));
    const select = $('#f-menu', form);
    const text = (name) => (form.elements[name] ? String(form.elements[name].value || '').trim() : '');
    const checked = (name) => form.querySelector(`input[name="${name}"]:checked`);
    const choiceLabel = (name) => {
      const input = checked(name);
      return input ? input.closest('label').querySelector('.c-choice__label').textContent : '';
    };
    const formatDate = (value) => {
      if (!value) return '指定なし';
      const [y, m, d] = value.split('-').map(Number);
      const date = new Date(Date.UTC(y, m - 1, d));
      return `${y}年${jaDate(date)}`;
    };

    const validate = () => {
      const errors = [];
      const add = (name, message) => errors.push({ name, message });
      const inquiry = checked('purpose')?.value === 'other';
      const required = (name, verb = '入力') => `${labels[name]}を${verb}してください。`;
      const controlChars = /[\u0000-\u001f\u007f]/;

      const name = text('name');
      if (!name) add('name', required('name'));
      else if (name.length > 40) add('name', 'お名前は40文字以内で入力してください。');
      else if (controlChars.test(name)) add('name', 'お名前に改行や制御文字は使えません。');

      const kana = toKatakana(text('kana'));
      if (!kana) add('kana', required('kana'));
      else if (!/^[ァ-ヶー・　 ]+$/.test(kana)) add('kana', 'フリガナはカタカナで入力してください。');

      const email = toHalfWidth(text('email'));
      if (!email) add('email', required('email'));
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 254) add('email', 'メールアドレスの形式が正しくありません。');

      const tel = toHalfWidth(text('tel'));
      const digits = tel.replace(/\D/g, '');
      if (!tel) add('tel', required('tel'));
      else if (/[^\d-]/.test(tel) || !/^0\d{9,10}$/.test(digits)) add('tel', '電話番号は半角数字とハイフンで入力してください。');

      if (!checked('purpose')) add('purpose', required('purpose', '選択'));

      [['date1', !inquiry], ['date2', false]].forEach(([field, isRequired]) => {
        const value = text(field);
        if (!value) {
          if (isRequired) add(field, required(field, '選択'));
        } else if (value < minDate) {
          add(field, `${labels[field]}は1日後以降の日付を選択してください。`);
        } else if (value > maxDate) {
          add(field, `${labels[field]}は60日以内の日付を選択してください。`);
        }
      });
      if (!inquiry && !checked('time')) add('time', required('time', '選択'));

      if (text('message').length > 1000) add('message', 'ご相談内容は1000文字以内で入力してください。');
      if (!form.elements.consent.checked) add('consent', 'プライバシーポリシーへの同意が必要です。');

      const order = Object.keys(labels);
      return errors.sort((a, b) => order.indexOf(a.name) - order.indexOf(b.name));
    };

    const controlOf = (name) => $(`#f-${name}`, form);
    const clearErrors = () => {
      $$('.js-demo-error', form).forEach((node) => node.remove());
      $$('.c-field.is-invalid', form).forEach((field) => field.classList.remove('is-invalid'));
      $$('[aria-invalid]', form).forEach((node) => node.removeAttribute('aria-invalid'));
      Object.keys(labels).forEach((name) => {
        const control = controlOf(name);
        if (!control) return;
        const hint = $(`#f-${name}-hint`, form);
        if (hint) control.setAttribute('aria-describedby', hint.id);
        else control.removeAttribute('aria-describedby');
      });
      $('.js-demo-notice')?.remove();
    };
    const showErrors = (errors) => {
      errors.forEach(({ name, message }) => {
        const control = controlOf(name);
        if (!control) return;
        const field = control.matches('fieldset') ? control : control.closest('.c-field');
        const error = create('p', 'c-field__error js-demo-error', message);
        error.id = `f-${name}-error`;
        field.classList.add('is-invalid');
        field.append(error);
        control.setAttribute('aria-invalid', 'true');
        const hint = $(`#f-${name}-hint`, form);
        control.setAttribute('aria-describedby', [hint?.id, error.id].filter(Boolean).join(' '));
      });

      const notice = create('div', 'c-form-notice js-demo-notice');
      notice.setAttribute('role', 'alert');
      notice.tabIndex = -1;
      const list = create('ul', 'c-form-notice__list');
      errors.forEach(({ name, message }) => {
        const link = create('a', '', message);
        link.href = `#f-${name}`;
        const item = create('li');
        item.append(link);
        list.append(item);
      });
      notice.append(create('p', 'c-form-notice__title', '入力内容に誤りがあります。各項目のメッセージをご確認ください。'), list);
      form.before(notice);
      notice.focus();
    };

    const setStep = (index) => {
      steps.forEach((step, i) => {
        step.classList.toggle('is-done', i < index);
        if (i === index) step.setAttribute('aria-current', 'step');
        else step.removeAttribute('aria-current');
      });
    };
    const mount = (templateId) => {
      const template = document.getElementById(templateId);
      const view = template.content.firstElementChild.cloneNode(true);
      view.classList.add('js-demo-view');
      stage.append(view);
      return view;
    };
    const unmount = () => $$('.js-demo-view', stage).forEach((view) => view.remove());
    // 完了後に入力画面へ戻すとき、補助表示もリセット後の値に合わせる
    const syncDemoDefaults = () => {
      $$('.js-closed-warning', form).forEach((node) => node.remove());
      $$('.js-counter', form).forEach((textarea) => textarea.dispatchEvent(new Event('input')));
      form.querySelector('input[name="purpose"]')?.dispatchEvent(new Event('change'));
    };
    const toInput = () => {
      unmount();
      form.hidden = false;
      setStep(0);
      controlOf('name')?.focus();
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      clearErrors();
      const errors = validate();
      if (errors.length) {
        showErrors(errors);
        return;
      }

      const values = {
        name: text('name'),
        kana: toKatakana(text('kana')),
        email: toHalfWidth(text('email')),
        tel: toHalfWidth(text('tel')),
        purpose: choiceLabel('purpose'),
        menu: select ? (select.value ? select.selectedOptions[0].textContent : '未選択') : '',
        date1: formatDate(text('date1')),
        time: choiceLabel('time') || '指定なし',
        date2: formatDate(text('date2')),
        message: text('message') || '（記入なし）',
        consent: '同意する',
      };
      form.hidden = true;
      const view = mount('demo-confirm');
      const list = $('.js-demo-list', view);
      Object.entries(labels).forEach(([name, label]) => {
        const row = create('div', 'c-confirm__row');
        row.append(create('dt', '', label), create('dd', '', values[name]));
        list.append(row);
      });
      setStep(1);
      $('h2', view).focus();

      $('.js-demo-back', view).addEventListener('click', toInput);
      $('.js-demo-send', view).addEventListener('click', () => {
        unmount();
        const done = mount('demo-complete');
        form.reset();
        syncDemoDefaults();
        setStep(2);
        $('h2', done).focus();
        $('.js-demo-restart', done).addEventListener('click', toInput);
      });
    });
  };

  initHeader();
  initDrawer();
  initViewTransition();
  initSpBar();
  initFilters();
  initTabs();
  initAccordions();
  initReception();
  initHoursToday();
  initToc();
  initGlaze();
  initForm();
})();
