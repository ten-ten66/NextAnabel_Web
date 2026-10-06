/**
 * 苔と麻 / koke to asa — main.js
 *
 * GSAP（gsap / ScrollTrigger / DrawSVGPlugin）は読み込んだページでだけ使う。
 * Swiper はギャラリーが画面に近づいたときに assets/vendor/ から読み込む（最初の表示を軽くするため）。
 * どの機能も「JavaScript がなくても本文が読める」状態から段階的に強化する。
 * 動きは gsap.matchMedia() の中だけで作り、動きを減らす設定では止まった完成形のまま表示する。
 */
(() => {
  'use strict';

  const root = document.documentElement;
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const prefersReducedMotion = () => reducedMotionQuery.matches;
  const gsap = window.gsap;
  const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const SVG_NS = 'http://www.w3.org/2000/svg';

  const siteData = (() => {
    try {
      return JSON.parse($('#site-data')?.textContent || '{}');
    } catch {
      return {};
    }
  })();

  /** 手が空いたときに実行する（最初の表示を妨げない） */
  const idle = (fn) => ('requestIdleCallback' in window ? window.requestIdleCallback(fn, { timeout: 1500 }) : window.setTimeout(fn, 200));

  /** 要素が画面に近づいたら一度だけ実行する */
  function whenNear(el, fn, rootMargin = '100% 0px') {
    if (!el) return;
    if (!('IntersectionObserver' in window)) {
      fn();
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      observer.disconnect();
      fn();
    }, { rootMargin });
    observer.observe(el);
  }

  /** 背面のスクロールを止めるとき、スクロールバーの幅だけ余白を足してレイアウトのずれを防ぐ */
  const measureScrollbar = () => {
    root.style.setProperty('--scrollbar-w', `${Math.max(0, window.innerWidth - root.clientWidth)}px`);
  };

  /** 東京時間の「曜日・月日・分」 */
  function tokyoNow() {
    const parts = Object.fromEntries(
      new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Tokyo',
        weekday: 'short',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
      }).formatToParts(new Date()).map((part) => [part.type, part.value]),
    );
    return {
      day: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(parts.weekday),
      monthDay: `${parts.month}-${parts.day}`,
      minutes: Number(parts.hour) * 60 + Number(parts.minute),
    };
  }

  const toMinutes = (hhmm) => {
    const [h, m] = hhmm.split(':').map(Number);
    return h * 60 + m;
  };
  const toClock = (minutes) => `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')}`;

  /* --------------------------------------------------------------------------
     通知（role="status"）と、デモ版の電話リンク
     -------------------------------------------------------------------------- */
  const toast = (() => {
    const el = $('.js-toast');
    let timer = 0;
    return (message) => {
      if (!el) return;
      window.clearTimeout(timer);
      el.textContent = '';
      // 同じ文言が続いても読み上げられるよう、次のフレームで入れ直す
      window.requestAnimationFrame(() => {
        el.textContent = message;
        el.classList.add('is-visible');
      });
      timer = window.setTimeout(() => el.classList.remove('is-visible'), 4000);
    };
  })();

  if (root.dataset.build === 'demo') {
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href^="tel:"]');
      if (!link) return;
      event.preventDefault();
      toast('サンプルサイトのため、電話はかかりません。');
    });
  }

  /* --------------------------------------------------------------------------
     ヘッダー：スクロールしたら背景を付ける
     -------------------------------------------------------------------------- */
  function initHeader() {
    const header = $('.js-header');
    if (!header || !('IntersectionObserver' in window)) return;
    const sentinel = document.createElement('div');
    sentinel.className = 'u-scroll-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    document.body.prepend(sentinel);
    new IntersectionObserver(([entry]) => {
      header.classList.toggle('is-scrolled', !entry.isIntersecting);
    }).observe(sentinel);
  }

  /* --------------------------------------------------------------------------
     モバイルのドロワー：aria-expanded / フォーカスの閉じ込め / Esc / 背景を inert に
     -------------------------------------------------------------------------- */
  function initDrawer() {
    const toggle = $('.js-drawer-toggle');
    const drawer = $('.js-drawer');
    if (!toggle || !drawer) return;
    const label = $('.js-drawer-label', toggle);
    const background = () => [
      $('.c-skip-link'), $('.c-demo-notice'), $('.l-header__logo'), $('.l-header__nav'),
      $('.l-header__cta'), $('main'), $('.l-footer'),
    ].filter(Boolean);
    let isOpen = false;
    let hideTimer = 0;

    const focusables = () => [toggle, ...$$(FOCUSABLE, drawer)];

    function onKeydown(event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== 'Tab') return;
      const items = focusables();
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      } else if (!items.includes(document.activeElement)) {
        event.preventDefault();
        first.focus();
      }
    }

    function open() {
      isOpen = true;
      window.clearTimeout(hideTimer);
      measureScrollbar();
      drawer.hidden = false;
      drawer.getBoundingClientRect(); // 表示を確定させてからトランジションを始める
      drawer.classList.add('is-open');
      root.classList.add('is-drawer-open');
      toggle.setAttribute('aria-expanded', 'true');
      if (label) label.textContent = '閉じる';
      background().forEach((el) => { el.inert = true; });
      document.addEventListener('keydown', onKeydown);
      $('.js-drawer-link', drawer)?.focus({ preventScroll: true });
    }

    function close({ restoreFocus = true } = {}) {
      if (!isOpen) return;
      isOpen = false;
      drawer.classList.remove('is-open');
      root.classList.remove('is-drawer-open');
      toggle.setAttribute('aria-expanded', 'false');
      if (label) label.textContent = 'メニュー';
      background().forEach((el) => { el.inert = false; });
      document.removeEventListener('keydown', onKeydown);
      const hide = () => { if (!isOpen) drawer.hidden = true; };
      if (prefersReducedMotion()) hide();
      else hideTimer = window.setTimeout(hide, 450);
      if (restoreFocus) toggle.focus();
    }

    toggle.addEventListener('click', () => (isOpen ? close() : open()));
    drawer.addEventListener('click', (event) => {
      if (event.target.closest('.js-drawer-link')) close({ restoreFocus: false });
    });
    window.matchMedia('(min-width: 64em)').addEventListener('change', (event) => {
      if (event.matches) close({ restoreFocus: false });
    });
  }

  /* --------------------------------------------------------------------------
     ダイアログ（<dialog>.showModal で背景は自動的に inert になり、Esc で閉じる）
     -------------------------------------------------------------------------- */
  function initDialogs() {
    $$('.js-dialog-open').forEach((button) => {
      const dialog = document.getElementById(button.getAttribute('aria-controls'));
      if (!dialog || typeof dialog.showModal !== 'function') return;
      button.hidden = false;
      button.addEventListener('click', () => {
        measureScrollbar();
        dialog.showModal();
        dialog.addEventListener('close', () => button.focus(), { once: true });
      });
    });
    $$('.js-dialog').forEach((dialog) => {
      dialog.addEventListener('click', (event) => {
        // 背景（::backdrop）のクリックは dialog 自身が対象になる
        if (event.target === dialog || event.target.closest('.js-dialog-close')) dialog.close();
      });
    });
  }

  /* --------------------------------------------------------------------------
     いまの季節（香りのこよみ）。静的な HTML はビルドした日の内容なので、表示日に合わせ直す
     -------------------------------------------------------------------------- */
  function initSeason() {
    const seasons = siteData.seasons;
    if (!Array.isArray(seasons) || !seasons.length) return;
    const { monthDay } = tokyoNow();
    let current = seasons[seasons.length - 1];
    seasons.forEach((season) => {
      if (season.start <= monthDay) current = season;
    });
    $$('.js-season-scent').forEach((el) => { el.textContent = current.scent; });
    $$('.js-season-sekki').forEach((el) => { el.textContent = current.sekki; });
    $$('.p-season__item').forEach((item) => {
      item.classList.toggle('is-current', item.dataset.sekki === current.sekki);
    });
  }

  /* --------------------------------------------------------------------------
     営業状況（東京時間で計算）
     -------------------------------------------------------------------------- */
  function initOpenStatus() {
    const el = $('.js-open-status');
    const schedule = siteData.schedule;
    if (!el || !schedule) return;
    const names = ['日', '月', '火', '水', '木', '金', '土'];
    const now = tokyoNow();
    const today = schedule[now.day];
    const nextOpen = () => {
      for (let i = 1; i <= 7; i += 1) {
        const day = (now.day + i) % 7;
        if (schedule[day]) return `${i === 1 ? '明日' : `${names[day]}曜`}は ${schedule[day].open} から営業します。`;
      }
      return '';
    };
    let state = 'closed';
    let text;
    if (!today) {
      text = `本日（${names[now.day]}曜）は定休日です。${nextOpen()}`;
    } else {
      const open = toMinutes(today.open);
      const close = toMinutes(today.close);
      const last = close - Number(siteData.lastEntryMinutes || 0);
      if (now.minutes < open) {
        text = `本日は ${today.open} から営業します（最終受付 ${toClock(last)}）。`;
      } else if (now.minutes < last) {
        state = 'open';
        text = `ただいま営業中です（本日の最終受付 ${toClock(last)}）。`;
      } else if (now.minutes < close) {
        text = `本日の受付は終了しました。${nextOpen()}`;
      } else {
        text = `本日の営業は終了しました。${nextOpen()}`;
      }
    }
    el.textContent = text;
    el.dataset.state = state;
    el.hidden = false;
  }

  /* --------------------------------------------------------------------------
     香りのこよみ：ゆっくり流れる帯。一時停止ボタン（WCAG 2.2.2）・画面外では停止
     -------------------------------------------------------------------------- */
  function initMarquee() {
    const section = $('.js-marquee');
    if (!section) return;
    const track = $('.js-marquee-track', section);
    const toggle = $('.js-marquee-toggle', section);
    const label = $('.js-marquee-label', section);
    const icon = $('.js-marquee-icon use', section);
    if (!track || !toggle) return;

    // 継ぎ目なく流すために同じ並びを複製する（読み上げは元の1組だけ）
    $$('.p-season__item', track).forEach((item) => {
      const clone = item.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      clone.classList.add('is-clone');
      track.append(clone);
    });

    let paused = false;
    const render = () => {
      section.classList.toggle('is-paused', paused);
      label.textContent = paused ? '再生' : '一時停止';
      icon?.setAttribute('href', paused ? '#i-play' : '#i-pause');
    };
    const setSpeed = () => {
      // 内容の幅にかかわらず、毎秒およそ26pxの一定の速さにする
      const distance = track.scrollWidth / 2;
      track.style.setProperty('--marquee-duration', `${Math.max(30, Math.round(distance / 26))}s`);
    };
    const apply = () => {
      const enabled = !prefersReducedMotion();
      section.classList.toggle('is-marquee', enabled);
      toggle.hidden = !enabled;
      $$('.is-clone', track).forEach((item) => { item.hidden = !enabled; });
    };

    toggle.addEventListener('click', () => {
      paused = !paused;
      render();
    });
    reducedMotionQuery.addEventListener('change', apply);
    if ('ResizeObserver' in window) new ResizeObserver(() => { if (!prefersReducedMotion()) setSpeed(); }).observe(track);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(([entry]) => {
        section.classList.toggle('is-offscreen', !entry.isIntersecting);
      }).observe(section);
    }
    apply();
    render();
  }

  /* --------------------------------------------------------------------------
     よくある質問：見出し＋button のアコーディオン
     JavaScript がなければ全て開いた状態。閉じた回答もページ内検索で開けるよう hidden="until-found" を使う
     -------------------------------------------------------------------------- */
  function initAccordion() {
    const untilFound = 'onbeforematch' in document.body;
    $$('.js-accordion-trigger').forEach((trigger) => {
      const panel = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!panel) return;
      const set = (expanded) => {
        trigger.setAttribute('aria-expanded', String(expanded));
        if (expanded) panel.removeAttribute('hidden');
        else if (untilFound) panel.setAttribute('hidden', 'until-found');
        else panel.hidden = true;
      };
      set(false);
      trigger.addEventListener('click', () => set(trigger.getAttribute('aria-expanded') !== 'true'));
      panel.addEventListener('beforematch', () => set(true));
    });
  }

  /* --------------------------------------------------------------------------
     料金表の目次：いま読んでいる区分を示す（aria-current）
     -------------------------------------------------------------------------- */
  function initPriceNav() {
    const nav = $('.js-price-nav');
    if (!nav || !('IntersectionObserver' in window)) return;
    const list = $('ul', nav);
    const links = new Map($$('a', nav).map((link) => [link.hash.slice(1), link]));
    const setCurrent = (id) => {
      links.forEach((link, key) => {
        if (key === id) {
          link.setAttribute('aria-current', 'true');
          const left = link.offsetLeft - (list.clientWidth - link.offsetWidth) / 2;
          list.scrollTo({ left, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
        } else {
          link.removeAttribute('aria-current');
        }
      });
    };
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => { if (entry.isIntersecting) setCurrent(entry.target.id); });
    }, { rootMargin: '-35% 0px -60% 0px' });
    $$('.js-price-section').forEach((section) => observer.observe(section));
  }

  /* --------------------------------------------------------------------------
     空間のギャラリー（Swiper）
     JavaScript がなければ横スクロールの一覧。画面に近づいたら Swiper を読み込んで、
     前後ボタン・ページ送り・←→キー・読み上げ用の状況表示を付ける
     -------------------------------------------------------------------------- */
  function loadSwiper(region) {
    if (typeof window.Swiper === 'function') return Promise.resolve();
    const css = region.dataset.swiperCss;
    const js = region.dataset.swiperJs;
    if (!js) return Promise.reject(new Error('Swiper の場所が指定されていません'));
    if (css) {
      // サイトの CSS より前に入れて、Swiper の既定のスタイルをサイト側で上書きできる順序にする
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = css;
      const own = $('link[rel="stylesheet"][href*="css/style.css"]');
      if (own) own.before(link);
      else document.head.append(link);
    }
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = js;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('Swiper を読み込めませんでした'));
      document.head.append(script);
    });
  }

  function initGallery() {
    const region = $('.js-gallery');
    if (!region) return;
    whenNear(region, () => {
      loadSwiper(region).then(() => setupGallery(region)).catch(() => {
        // 読み込めなくても、横スクロールの一覧のまま使える
      });
    }, '120% 0px');
  }

  function setupGallery(region) {
    if (typeof window.Swiper !== 'function') return;
    const container = $('.js-gallery-swiper', region);
    const wrapper = $('.js-gallery-wrapper', region);
    const controls = $('.js-gallery-controls', region);
    const prev = $('.js-gallery-prev', region);
    const next = $('.js-gallery-next', region);
    const current = $('.js-gallery-current', region);
    const status = $('.js-gallery-status', region);
    const slides = $$('.swiper-slide', container);
    let swiper = null;

    wrapper.removeAttribute('tabindex');
    wrapper.scrollLeft = 0;
    controls.hidden = false;
    $('.js-gallery-hint', region)?.setAttribute('hidden', '');

    const update = (instance) => {
      const index = instance.activeIndex;
      current.textContent = String(index + 1).padStart(2, '0');
      prev.setAttribute('aria-disabled', String(instance.isBeginning));
      next.setAttribute('aria-disabled', String(instance.isEnd));
    };
    const announce = (instance) => {
      const index = instance.activeIndex;
      status.textContent = `${slides.length}枚中${index + 1}枚目：${slides[index]?.dataset.title ?? ''}`;
    };
    // 最後の1枚も左端まで送れるよう、終端に余白を足す
    const fitEnd = (instance) => {
      const last = instance.slides[instance.slides.length - 1];
      const after = Math.max(0, Math.round(instance.width - last.offsetWidth));
      if (instance.params.slidesOffsetAfter !== after) {
        instance.params.slidesOffsetAfter = after;
        instance.update();
      }
    };

    const build = () => {
      const reduce = prefersReducedMotion();
      if (swiper) swiper.destroy(true, true);
      swiper = new window.Swiper(container, {
        slidesPerView: 'auto',
        spaceBetween: 16,
        speed: reduce ? 0 : 900,
        parallax: !reduce,
        grabCursor: true,
        watchSlidesProgress: true,
        breakpoints: {
          768: { spaceBetween: 24 },
          1024: { spaceBetween: 32 },
        },
        pagination: {
          el: $('.js-gallery-pagination', region),
          clickable: true,
          bulletElement: 'button',
        },
        a11y: {
          paginationBulletMessage: '{{index}}枚目の写真を表示',
          slideLabelMessage: '{{index}} / {{slidesLength}}',
          itemRoleDescriptionMessage: 'スライド',
          firstSlideMessage: '最初の写真です',
          lastSlideMessage: '最後の写真です',
          wrapperLiveRegion: false,
        },
        on: {
          init(instance) {
            fitEnd(instance);
            update(instance);
          },
          resize: fitEnd,
          slideChange(instance) {
            update(instance);
            announce(instance);
          },
          reachEnd: update,
          reachBeginning: update,
        },
      });
    };

    prev.addEventListener('click', () => { if (prev.getAttribute('aria-disabled') !== 'true') swiper.slidePrev(); });
    next.addEventListener('click', () => { if (next.getAttribute('aria-disabled') !== 'true') swiper.slideNext(); });
    // ←→ / Home / End は、フォーカスがカルーセルの中にあるときだけ反応させる（ページのスクロールは奪わない）
    region.addEventListener('keydown', (event) => {
      const keys = {
        ArrowLeft: () => swiper.slidePrev(),
        ArrowRight: () => swiper.slideNext(),
        Home: () => swiper.slideTo(0),
        End: () => swiper.slideTo(slides.length - 1),
      };
      if (!keys[event.key] || event.altKey || event.ctrlKey || event.metaKey) return;
      event.preventDefault();
      keys[event.key]();
    });
    reducedMotionQuery.addEventListener('change', build);
    build();
  }

  /* --------------------------------------------------------------------------
     施術の流れ：ステップの番号を通る茎と葉を、レイアウトから SVG で組み立てる
     スクロールに合わせて DrawSVGPlugin で描く。動きを減らす設定・JavaScript なしでは描き終えた状態
     （画面に近づいてから組み立てるので、最初の表示の負担にならない）
     -------------------------------------------------------------------------- */
  function createVine() {
    const body = $('.js-vine');
    if (!body) return null;
    const svg = $('.js-vine-svg', body);
    const stem = $('.js-vine-stem', body);
    const group = $('.js-vine-leaves', body);
    const nodes = $$('.js-vine-node', body);
    let shape = null;
    let timeline = null;
    let animated = false;
    let pending = 0;

    // 位置から決まる疑似乱数（再描画しても同じ形になる）
    const random = (i) => {
      const x = Math.sin(i * 127.1 + 311.7) * 43758.5453;
      return x - Math.floor(x);
    };
    const fixed = (n) => Math.round(n * 10) / 10;

    function leafPath(point, angle, length, side) {
      const width = length * 0.42;
      const cos = Math.cos(angle);
      const sin = Math.sin(angle);
      const bx = point.x + cos * 5;
      const by = point.y + sin * 5;
      const p = (x, y) => `${fixed(bx + x * cos - y * sin)} ${fixed(by + x * sin + y * cos)}`;
      const s = side;
      const g = document.createElementNS(SVG_NS, 'g');
      const outline = document.createElementNS(SVG_NS, 'path');
      const rib = document.createElementNS(SVG_NS, 'path');
      outline.setAttribute('class', 'p-flow__leaf-outline');
      rib.setAttribute('class', 'p-flow__leaf-rib');
      outline.setAttribute('d', `M${p(0, 0)}C${p(length * 0.18, -width * 0.95 * s)} ${p(length * 0.62, -width * 1.05 * s)} ${p(length, 0)}C${p(length * 0.66, width * 0.9 * s)} ${p(length * 0.2, width * 0.85 * s)} ${p(0, 0)}Z`);
      rib.setAttribute('d', `M${fixed(point.x)} ${fixed(point.y)}L${p(0, 0)}Q${p(length * 0.5, width * 0.1 * s)} ${p(length * 0.88, 0)}`);
      g.append(outline, rib);
      return g;
    }

    function build() {
      const box = body.getBoundingClientRect();
      const width = svg.getBoundingClientRect().width;
      const height = box.height;
      if (!width || !height || !nodes.length) return false;
      const points = nodes.map((node) => {
        const rect = node.getBoundingClientRect();
        return { x: rect.left - box.left + rect.width / 2, y: rect.top - box.top + rect.height / 2, r: rect.width / 2 };
      });
      const amp = Math.min(width * 0.32, 28);
      const first = points[0];
      let d = `M${fixed(first.x)} 0C${fixed(first.x)} ${fixed(first.y * 0.45)} ${fixed(first.x + amp * 0.5)} ${fixed(first.y * 0.55)} ${fixed(first.x)} ${fixed(first.y)}`;
      for (let i = 0; i < points.length - 1; i += 1) {
        const a = points[i];
        const b = points[i + 1];
        const mid = (a.y + b.y) / 2;
        const dy = b.y - a.y;
        const mx = a.x + (i % 2 === 0 ? -amp : amp);
        d += `C${fixed(a.x)} ${fixed(a.y + dy * 0.2)} ${fixed(mx)} ${fixed(mid - dy * 0.24)} ${fixed(mx)} ${fixed(mid)}`;
        d += `C${fixed(mx)} ${fixed(mid + dy * 0.24)} ${fixed(b.x)} ${fixed(b.y - dy * 0.2)} ${fixed(b.x)} ${fixed(b.y)}`;
      }
      // 最後のステップのあとに、くるりと巻いた蔓を添える
      const last = points[points.length - 1];
      const tail = Math.min(110, height - last.y - 6);
      if (tail > 48) {
        const t = tail;
        const x = last.x;
        const y = last.y;
        const k = amp * 0.85;
        d += `C${fixed(x)} ${fixed(y + t * 0.38)} ${fixed(x + k)} ${fixed(y + t * 0.42)} ${fixed(x + k)} ${fixed(y + t * 0.7)}`;
        d += `C${fixed(x + k)} ${fixed(y + t * 0.96)} ${fixed(x + k * 0.1)} ${fixed(y + t)} ${fixed(x + k * 0.18)} ${fixed(y + t * 0.8)}`;
        d += `C${fixed(x + k * 0.24)} ${fixed(y + t * 0.68)} ${fixed(x + k * 0.62)} ${fixed(y + t * 0.7)} ${fixed(x + k * 0.56)} ${fixed(y + t * 0.83)}`;
      }

      svg.setAttribute('viewBox', `0 0 ${fixed(width)} ${fixed(height)}`);
      svg.removeAttribute('preserveAspectRatio');
      stem.removeAttribute('vector-effect');
      stem.setAttribute('d', d);
      group.replaceChildren();

      const total = stem.getTotalLength();
      // 各ステップの番号が茎のどの長さにあるか（茎を細かく区切って一番近い点を探す）
      const samples = [];
      for (let l = 0; l <= total; l += 6) samples.push({ l, p: stem.getPointAtLength(l) });
      const lengthAt = (pt) => samples.reduce((best, s) => {
        const dist = Math.hypot(s.p.x - pt.x, s.p.y - pt.y);
        return dist < best.dist ? { dist, l: s.l } : best;
      }, { dist: Infinity, l: 0 }).l;

      const leaves = [];
      const gap = width > 80 ? 38 : 32;
      const size = width > 80 ? 31 : 21;
      let side = 1;
      for (let l = 30, i = 0; l < total - 20; l += gap * (0.8 + random(i) * 0.4), i += 1) {
        const pt = stem.getPointAtLength(l);
        if (points.some((n) => Math.hypot(n.x - pt.x, n.y - pt.y) < n.r + 10)) continue;
        const ahead = stem.getPointAtLength(Math.min(total, l + 1));
        const tangent = Math.atan2(ahead.y - pt.y, ahead.x - pt.x);
        const angle = tangent + side * (0.78 + random(i + 3) * 0.3);
        const leaf = leafPath(pt, angle, size * (0.8 + random(i + 7) * 0.4), side);
        group.append(leaf);
        leaves.push({ el: leaf, at: l / total });
        side *= -1;
      }
      const rings = points.map((n) => {
        const ring = document.createElementNS(SVG_NS, 'circle');
        ring.setAttribute('class', 'p-flow__ring');
        ring.setAttribute('cx', fixed(n.x));
        ring.setAttribute('cy', fixed(n.y));
        ring.setAttribute('r', fixed(n.r + 6));
        ring.setAttribute('transform', `rotate(-90 ${fixed(n.x)} ${fixed(n.y)})`);
        group.append(ring);
        return { el: ring, at: lengthAt(n) / total };
      });
      shape = { leaves, rings };
      return true;
    }

    function kill() {
      if (!timeline) return;
      timeline.scrollTrigger?.kill();
      timeline.kill();
      timeline = null;
      gsap.set([stem, ...$$('path, circle', group)], { clearProps: 'all' });
    }

    function animate() {
      kill();
      if (!animated || !shape || !gsap || !window.ScrollTrigger || !window.DrawSVGPlugin) return;
      timeline = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: { trigger: body, start: 'top 78%', end: 'bottom 72%', scrub: 0.8 },
      });
      timeline.fromTo(stem, { drawSVG: '0%' }, { drawSVG: '100%', duration: 1 }, 0);
      shape.leaves.forEach(({ el, at }) => {
        const [outline, rib] = el.children;
        timeline.fromTo([rib, outline], { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.07, stagger: 0.02 }, at);
        timeline.fromTo(outline, { fillOpacity: 0 }, { fillOpacity: 1, duration: 0.06 }, at + 0.05);
      });
      shape.rings.forEach(({ el, at }) => {
        timeline.fromTo(el, { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.09 }, Math.max(0, at - 0.03));
      });
    }

    function rebuild() {
      window.cancelAnimationFrame(pending);
      pending = window.requestAnimationFrame(() => {
        if (build()) animate();
      });
    }

    whenNear(body, () => {
      if ('ResizeObserver' in window) new ResizeObserver(rebuild).observe(body);
      else rebuild();
    });

    return {
      setAnimated(value) {
        animated = value;
        if (shape) animate();
      },
    };
  }

  /* --------------------------------------------------------------------------
     動き（GSAP）。gsap.matchMedia() で「動きを減らす設定」の分岐を作る
     -------------------------------------------------------------------------- */
  function initMotion(vine) {
    const finishDrawing = () => root.classList.remove('is-drawing', 'is-drawing-running');
    if (!gsap) {
      finishDrawing();
      return;
    }
    if (window.ScrollTrigger) gsap.registerPlugin(window.ScrollTrigger);
    if (window.DrawSVGPlugin) gsap.registerPlugin(window.DrawSVGPlugin);
    const supportsScrollTimeline = window.CSS?.supports?.('animation-timeline: view()') ?? false;
    let introDone = false;

    /* (1) ファーストビューの読み込み演出：文字は CSS が表示し、ここでは植物の線画を描く（1.2秒以内） */
    function playIntro() {
      if (introDone) return;
      introDone = true;
      const hero = $('.js-hero');
      const targets = $$('[data-intro="draw"]');
      // 線画がない、または読み込みが遅くフェイルセーフで表示済みなら、演出しない
      if (!hero || !targets.length || !window.DrawSVGPlugin || performance.now() > 1800) {
        finishDrawing();
        return;
      }
      root.classList.add('is-drawing-running');
      const stems = $$('.js-branch-stem', hero);
      const leaves = $$('.js-branch-leaf', hero);
      const leafPaths = leaves.flatMap((leaf) => Array.from(leaf.children));
      const swash = $('.js-hero-swash', hero);
      const tl = gsap.timeline({
        defaults: { ease: 'power2.inOut' },
        onComplete() {
          gsap.set([...targets, ...stems, ...leafPaths, swash], { clearProps: 'opacity,visibility,strokeDasharray,strokeDashoffset' });
          finishDrawing();
        },
      });
      // 画像そのものは CSS（scale: -1 1）で左右反転しているため、外側の要素を拡大縮小する
      tl.fromTo($('.p-hero__layer--far .js-hero-depth', hero), { scale: 1.06 }, { scale: 1, duration: 1.2, ease: 'power2.out' }, 0)
        .set(targets, { autoAlpha: 1 }, 0)
        .fromTo($('.p-hero__branch--shade', hero), { autoAlpha: 0 }, { autoAlpha: 1, duration: 1, ease: 'sine.out' }, 0.1)
        .fromTo(stems[0], { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.95 }, 0)
        .fromTo(stems.slice(1), { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.5, ease: 'power2.out' }, 0.38)
        .fromTo(swash, { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.6 }, 0.44);
      // 葉は、茎が届く順に少しずつ遅らせて描く
      leaves.forEach((leaf) => {
        const at = Number(leaf.dataset.at) || 0;
        tl.fromTo(leaf.children, { drawSVG: '0%' }, { drawSVG: '100%', duration: 0.38, ease: 'power1.out' }, 0.1 + at * 0.72);
      });
      if (tl.duration() > 1.2) tl.timeScale(tl.duration() / 1.2);
    }

    /* (3) パララックスの代替（CSS のスクロール駆動アニメーションに未対応のブラウザ） */
    function parallaxFallback() {
      if (supportsScrollTimeline || !window.ScrollTrigger) return;
      $$('.c-parallax [data-parallax]').forEach((img) => {
        gsap.fromTo(img, { yPercent: -7 }, {
          yPercent: 7,
          ease: 'none',
          scrollTrigger: { trigger: img.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
        });
      });
      const hero = $('.js-hero');
      if (!hero) return;
      $$('.p-hero__layer', hero).forEach((layer) => {
        const shift = parseFloat(getComputedStyle(layer).getPropertyValue('--hero-shift')) || 10;
        gsap.to(layer, {
          yPercent: shift,
          ease: 'none',
          scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
        });
      });
    }

    /* (4) 主要なボタンの磁石のような追従（マウスなど細かく指せる環境のみ） */
    function magnetic() {
      const cleanups = $$('.js-magnet').map((wrap) => {
        const target = wrap.firstElementChild;
        if (!target) return null;
        const label = $('.c-button__label', target);
        const tween = (el, prop) => gsap.quickTo(el, prop, { duration: 0.7, ease: 'power3.out' });
        const x = tween(target, 'x');
        const y = tween(target, 'y');
        const lx = label ? tween(label, 'x') : null;
        const ly = label ? tween(label, 'y') : null;
        let rect = null;
        const onEnter = () => { rect = wrap.getBoundingClientRect(); };
        const onMove = (event) => {
          rect ??= wrap.getBoundingClientRect();
          const dx = event.clientX - (rect.left + rect.width / 2);
          const dy = event.clientY - (rect.top + rect.height / 2);
          x(dx * 0.22);
          y(dy * 0.32);
          lx?.(dx * 0.1);
          ly?.(dy * 0.12);
        };
        const onLeave = () => {
          rect = null;
          x(0);
          y(0);
          lx?.(0);
          ly?.(0);
        };
        wrap.addEventListener('pointerenter', onEnter);
        wrap.addEventListener('pointermove', onMove);
        wrap.addEventListener('pointerleave', onLeave);
        return () => {
          wrap.removeEventListener('pointerenter', onEnter);
          wrap.removeEventListener('pointermove', onMove);
          wrap.removeEventListener('pointerleave', onLeave);
          gsap.set([target, label].filter(Boolean), { clearProps: 'transform' });
        };
      });
      return () => cleanups.forEach((fn) => fn?.());
    }

    /* ファーストビューの葉影：ポインターの位置で奥行きごとにわずかにずれる */
    function heroDepth() {
      const hero = $('.js-hero');
      if (!hero) return () => {};
      const layers = $$('.js-hero-depth', hero).map((el) => ({
        el,
        depth: Number(el.dataset.depth) || 0,
        x: gsap.quickTo(el, 'x', { duration: 1.4, ease: 'power3.out' }),
        y: gsap.quickTo(el, 'y', { duration: 1.4, ease: 'power3.out' }),
      }));
      const onMove = (event) => {
        const nx = event.clientX / window.innerWidth - 0.5;
        const ny = event.clientY / window.innerHeight - 0.5;
        layers.forEach((layer) => {
          layer.x(-nx * layer.depth);
          layer.y(-ny * layer.depth);
        });
      };
      const onLeave = () => layers.forEach((layer) => { layer.x(0); layer.y(0); });
      hero.addEventListener('pointermove', onMove);
      hero.addEventListener('pointerleave', onLeave);
      return () => {
        hero.removeEventListener('pointermove', onMove);
        hero.removeEventListener('pointerleave', onLeave);
        gsap.set(layers.map((layer) => layer.el), { clearProps: 'transform' });
      };
    }

    const mm = gsap.matchMedia();
    mm.add({
      motion: '(prefers-reduced-motion: no-preference)',
      reduce: '(prefers-reduced-motion: reduce)',
      fine: '(hover: hover) and (pointer: fine)',
    }, (context) => {
      const { motion, fine } = context.conditions;
      if (!motion) {
        // 動きを減らす設定：演出を作らず、描き終えた完成形のまま
        introDone = true;
        finishDrawing();
        vine?.setAnimated(false);
        return undefined;
      }
      playIntro();
      parallaxFallback();
      vine?.setAnimated(true);
      const cleanups = fine ? [magnetic(), heroDepth()] : [];
      return () => {
        vine?.setAnimated(false);
        cleanups.forEach((fn) => fn());
      };
    });
  }

  /* -------------------------------------------------------------------------- */
  initHeader();
  initDrawer();
  initDialogs();
  initSeason();
  initOpenStatus();
  initAccordion();
  initPriceNav();
  initMotion(createVine());
  initGallery();
  idle(initMarquee);
})();
