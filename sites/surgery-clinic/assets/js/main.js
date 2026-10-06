/**
 * オルヴァン美容外科 — main.js
 * 依存: GSAP（gsap / ScrollTrigger / SplitText）。読み込めなかった場合も本文はすべて読める。
 *
 *   - ヘッダーの縮小（IntersectionObserver）
 *   - モバイルのドロワーメニュー（aria-expanded・フォーカスの閉じ込め・Esc で閉じる）
 *   - tel: リンクの通知（demo 版のみ）
 *   - 施術ページの目次の現在地表示
 *   - フォーム: 静的版はデモ表示（入力 → 確認 → 完了）、サーバー版は二重送信の防止
 *   - GSAP: 見出しの文字分割、ヒーローの拡大、画像のパララックス、施術の流れの横スクロール
 */
'use strict';

(() => {
  const root = document.documentElement;
  const isDemo = root.dataset.build === 'demo';
  const isStatic = root.dataset.env === 'static';
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  /* ---------------------------------------------------------------------
     通知（role="status" の領域に表示する）
     --------------------------------------------------------------------- */
  let toastTimer = 0;
  const showToast = (message) => {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.classList.remove('is-visible');
    toast.textContent = '';
    window.requestAnimationFrame(() => {
      toast.textContent = message;
      toast.classList.add('is-visible');
    });
    toastTimer = window.setTimeout(() => {
      toast.classList.remove('is-visible');
      toastTimer = window.setTimeout(() => {
        toast.textContent = '';
      }, 500);
    }, 3200);
  };

  /* ---------------------------------------------------------------------
     ヘッダー: ページ上端から離れたら縮小表示
     --------------------------------------------------------------------- */
  const initHeader = () => {
    const header = document.querySelector('[data-header]');
    if (!header || !('IntersectionObserver' in window)) return;
    const sentinel = document.createElement('div');
    sentinel.className = 'u-scroll-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    document.body.prepend(sentinel);
    new IntersectionObserver(([entry]) => {
      header.classList.toggle('is-compact', !entry.isIntersecting);
    }).observe(sentinel);
  };

  /* ---------------------------------------------------------------------
     ドロワーメニュー
     --------------------------------------------------------------------- */
  const initDrawer = () => {
    const toggle = document.querySelector('[data-drawer-toggle]');
    const drawer = toggle && document.getElementById(toggle.getAttribute('aria-controls'));
    if (!toggle || !drawer) return;
    const label = toggle.querySelector('[data-drawer-label]');
    const outside = ['.c-skip-link', '.c-demo-notice', 'main', 'footer']
      .map((selector) => document.querySelector(selector))
      .filter(Boolean);
    let hideTimer = 0;

    const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';
    const focusables = () => [
      toggle,
      ...drawer.querySelectorAll('a[href], button:not([disabled])'),
    ];

    const onKeydown = (event) => {
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
    };

    const open = () => {
      window.clearTimeout(hideTimer);
      drawer.hidden = false;
      // hidden を外した直後のフレームでクラスを付け、フェードを効かせる
      window.requestAnimationFrame(() => window.requestAnimationFrame(() => drawer.classList.add('is-open')));
      toggle.setAttribute('aria-expanded', 'true');
      if (label) label.textContent = '閉じる';
      root.classList.add('is-drawer-open');
      outside.forEach((el) => { el.inert = true; });
      document.addEventListener('keydown', onKeydown);
      const firstLink = drawer.querySelector('a[href]');
      if (firstLink) firstLink.focus({ preventScroll: true });
    };

    const close = ({ restoreFocus = true } = {}) => {
      if (!isOpen()) return;
      toggle.setAttribute('aria-expanded', 'false');
      if (label) label.textContent = 'メニュー';
      drawer.classList.remove('is-open');
      root.classList.remove('is-drawer-open');
      outside.forEach((el) => { el.inert = false; });
      document.removeEventListener('keydown', onKeydown);
      hideTimer = window.setTimeout(() => { drawer.hidden = true; }, reducedMotion.matches ? 0 : 420);
      if (restoreFocus) toggle.focus();
    };

    toggle.addEventListener('click', () => (isOpen() ? close() : open()));
    // ページ内リンク（例: トップページの施術メニュー）を選んだら閉じる
    drawer.addEventListener('click', (event) => {
      const link = event.target.closest('a[href]');
      if (link && link.hash && link.pathname === window.location.pathname) close({ restoreFocus: false });
    });
    window.matchMedia('(min-width: 64em)').addEventListener('change', (event) => {
      if (event.matches) close({ restoreFocus: false });
    });
    // 戻るボタンでページが復元されたときに、開いたままにならないようにする
    window.addEventListener('pagehide', () => close({ restoreFocus: false }));
  };

  /* ---------------------------------------------------------------------
     tel: リンク（demo 版では発信せずに通知する）
     --------------------------------------------------------------------- */
  const initTelNotice = () => {
    if (!isDemo) return;
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href^="tel:"]');
      if (!link) return;
      event.preventDefault();
      showToast('サンプルサイトのため、電話はかかりません。');
    });
  };

  /* ---------------------------------------------------------------------
     目次の現在地表示（施術ページ・プライバシーポリシー）
     --------------------------------------------------------------------- */
  const initToc = () => {
    const links = [...document.querySelectorAll('.p-treatment__toc a[href^="#"], .p-document__toc a[href^="#"]')];
    if (!links.length || !('IntersectionObserver' in window)) return;
    const byId = new Map(links.map((link) => [decodeURIComponent(link.hash.slice(1)), link]));
    const sections = [...byId.keys()].map((id) => document.getElementById(id)).filter(Boolean);
    // 画面の上から30%の線を越えた最後の見出しを現在地にする（どれも越えていなければ解除）
    const update = () => {
      const line = window.innerHeight * 0.3;
      let current = null;
      sections.forEach((section) => {
        if (section.getBoundingClientRect().top <= line) current = section;
      });
      links.forEach((link) => link.classList.toggle('is-active', current !== null && byId.get(current.id) === link));
    };
    const observer = new IntersectionObserver(update, { rootMargin: '0px 0px -70% 0px' });
    sections.forEach((section) => observer.observe(section));
  };

  /* ---------------------------------------------------------------------
     フォーム共通
     --------------------------------------------------------------------- */
  // サーバー版の確認・完了・エラー表示: 読み上げの起点を移し、手順の表示が見える位置までスクロールする
  const focusOnLoad = () => {
    const target = document.querySelector('[data-focus-on-load]:not([hidden])');
    if (!target) return;
    target.focus({ preventScroll: true });
    (document.querySelector('[data-form-steps]') || target).scrollIntoView({ block: 'start' });
  };

  // 二重送信の防止。押したボタンの name/value を送信データに残すため、disabled にはしない
  const initSubmitOnce = () => {
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
          event.preventDefault();
          return;
        }
        form.dataset.submitting = 'true';
        form.querySelectorAll('button[type="submit"]').forEach((button) => button.setAttribute('aria-disabled', 'true'));
      });
    });
  };

  // エラー一覧のリンクから、該当する入力欄へ移動してフォーカスする
  const initErrorLinks = () => {
    document.addEventListener('click', (event) => {
      const link = event.target.closest('[data-error-summary] a[href^="#"]');
      if (!link) return;
      const field = document.getElementById(link.hash.slice(1));
      if (!field) return;
      event.preventDefault();
      field.focus({ preventScroll: true });
      field.closest('.c-field')?.scrollIntoView({ block: 'center', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    });
  };

  /* ---------------------------------------------------------------------
     静的版のフォームデモ（入力 → 確認 → 完了。送信はしない）
     入力値は textContent でのみ出力する。
     --------------------------------------------------------------------- */
  const pad = (n) => String(n).padStart(2, '0');
  const isoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  const addDays = (days) => {
    const date = new Date();
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() + days);
    return date;
  };
  const dateLabel = (value) => {
    const [y, m, d] = value.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    return `${y}年${m}月${d}日（${'日月火水木金土'[date.getDay()]}）`;
  };
  const toHalfWidth = (value) => value.replace(/[０-９Ａ-Ｚａ-ｚ＠．－ー―‐]/g, (ch) => {
    if ('－ー―‐'.includes(ch)) return '-';
    return String.fromCharCode(ch.charCodeAt(0) - 0xfee0);
  });
  const toKatakana = (value) => value.replace(/[ぁ-ゖ]/g, (ch) => String.fromCharCode(ch.charCodeAt(0) + 0x60));

  const validateDemo = (form) => {
    const data = new FormData(form);
    const text = (name) => String(data.get(name) ?? '').trim();
    const errors = [];
    const add = (name, message) => errors.push({ name, message });
    const controls = /[\u0000-\u001f\u007f]/;

    const name = text('name');
    if (!name) add('name', 'お名前を入力してください。');
    else if (name.length > 50 || controls.test(name)) add('name', 'お名前は50文字以内で、改行を含めずに入力してください。');

    const kana = toKatakana(text('kana'));
    if (!kana) add('kana', 'フリガナを入力してください。');
    else if (!/^[ァ-ヶー・　 ]+$/.test(kana)) add('kana', 'フリガナはカタカナで入力してください。');

    const email = toHalfWidth(text('email'));
    if (!email) add('email', 'メールアドレスを入力してください。');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) add('email', 'メールアドレスの形式が正しくありません。');

    const tel = toHalfWidth(text('tel'));
    if (!tel) add('tel', '電話番号を入力してください。');
    else if (/[^\d-]/.test(tel) || !/^0\d{9,10}$/.test(tel.replace(/-/g, ''))) add('tel', '電話番号は半角数字とハイフンで入力してください。');

    if (!data.getAll('menu[]').length) add('menu', 'ご希望の施術を選択してください。');

    const min = isoDate(addDays(1));
    const max = isoDate(addDays(60));
    const checkDate = (field, label, required) => {
      const value = text(field);
      if (!value) {
        if (required) add(field, `${label}を選択してください。`);
        return;
      }
      if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) add(field, `${label}の形式が正しくありません。`);
      else if (value < min) add(field, `${label}は1日後以降の日付を選択してください。`);
      else if (value > max) add(field, `${label}は60日以内の日付を選択してください。`);
    };
    checkDate('date1', '第1希望日', true);
    if (!data.get('time')) add('time', 'ご希望の時間帯を選択してください。');
    checkDate('date2', '第2希望日', false);

    if (text('message').length > 1000) add('message', 'ご相談内容は1000文字以内で入力してください。');
    if (data.get('consent') !== '1') add('consent', 'プライバシーポリシーへの同意が必要です。');

    // 表示の順序をフォームの並びにそろえる
    const order = ['name', 'kana', 'email', 'tel', 'menu', 'date1', 'date2', 'time', 'message', 'consent'];
    errors.sort((a, b) => order.indexOf(a.name) - order.indexOf(b.name));
    return errors;
  };

  const renderErrors = (form, errors, summary) => {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
      el.textContent = '';
      el.hidden = true;
    });
    form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    const list = summary?.querySelector('[data-error-list]');
    if (list) list.replaceChildren();

    errors.forEach(({ name, message }) => {
      const holder = form.querySelector(`[data-error-for="${name}"]`);
      if (holder) {
        holder.textContent = message;
        holder.hidden = false;
      }
      const field = form.querySelector(`[data-field="${name}"]`);
      field?.querySelectorAll('input, textarea').forEach((input) => input.setAttribute('aria-invalid', 'true'));
      if (list) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        const target = field?.querySelector('input, textarea');
        link.href = `#${target ? target.id : ''}`;
        link.textContent = message;
        item.append(link);
        list.append(item);
      }
    });
    if (summary) summary.hidden = errors.length === 0;
  };

  const initDemoForm = () => {
    const form = document.querySelector('form[data-demo-form]');
    if (!form || !isStatic) return;
    const container = form.closest('[data-form-root]');
    const confirm = container.querySelector('[data-demo-confirm]');
    const complete = container.querySelector('[data-demo-complete]');
    const summary = container.querySelector('[data-error-summary]');
    const steps = [...document.querySelectorAll('[data-form-steps] > li')];

    // 静的版では日付の範囲をその日の日付から設定する
    form.querySelectorAll('input[type="date"]').forEach((input) => {
      input.min = isoDate(addDays(Number(input.dataset.minDays || 0)));
      input.max = isoDate(addDays(Number(input.dataset.maxDays || 90)));
    });

    const setStep = (index) => {
      steps.forEach((step, i) => {
        step.classList.toggle('is-current', i === index);
        step.classList.toggle('is-done', i < index);
        if (i === index) step.setAttribute('aria-current', 'step');
        else step.removeAttribute('aria-current');
      });
    };
    const show = (panel) => {
      [form, confirm, complete].forEach((el) => { el.hidden = el !== panel; });
      const scrollTarget = document.querySelector('[data-form-steps]') || panel;
      scrollTarget.scrollIntoView({ block: 'start', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
      if (panel !== form) panel.focus({ preventScroll: true });
    };
    const labelsOf = (selector) => [...form.querySelectorAll(selector)]
      .map((input) => input.closest('label')?.querySelector('.c-choice__text')?.textContent.trim())
      .filter(Boolean)
      .join('、');

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const errors = validateDemo(form);
      renderErrors(form, errors, summary);
      if (errors.length) {
        summary?.focus();
        return;
      }
      const data = new FormData(form);
      const values = {
        name: String(data.get('name')).trim(),
        kana: toKatakana(String(data.get('kana')).trim()),
        email: toHalfWidth(String(data.get('email')).trim()),
        tel: toHalfWidth(String(data.get('tel')).trim()),
        menu: labelsOf('input[name="menu[]"]:checked'),
        date1: dateLabel(String(data.get('date1'))),
        time: labelsOf('input[name="time"]:checked'),
        date2: data.get('date2') ? dateLabel(String(data.get('date2'))) : '指定なし',
        message: String(data.get('message')).trim() || '記入なし',
      };
      Object.entries(values).forEach(([key, value]) => {
        const cell = confirm.querySelector(`[data-confirm="${key}"]`);
        if (cell) cell.textContent = value;
      });
      setStep(1);
      show(confirm);
    });

    confirm.querySelector('[data-demo-back]')?.addEventListener('click', () => {
      setStep(0);
      show(form);
      form.querySelector('input, textarea')?.focus({ preventScroll: true });
    });
    confirm.querySelector('[data-demo-send]')?.addEventListener('click', () => {
      setStep(2);
      show(complete);
      showToast('サンプルのため、入力内容は送信されていません。');
    });
  };

  /* ---------------------------------------------------------------------
     GSAP の演出（動きを減らす設定では何もしない）
     --------------------------------------------------------------------- */
  const revealTitles = () => {
    document.querySelectorAll('[data-split-intro]').forEach((el) => el.classList.add('is-split'));
  };

  const introTitle = (gsap, SplitText, el) => {
    const masked = el.hasAttribute('data-split-mask');
    const split = SplitText.create(el, { type: 'chars', charsClass: 'c-split-char', aria: 'auto' });
    el.classList.add('is-split');
    // 1.2 秒以内に終える（duration + stagger の合計）。終わったら元のマークアップに戻す
    gsap.from(split.chars, {
      yPercent: masked ? 112 : 55,
      autoAlpha: masked ? 1 : 0,
      duration: masked ? 0.9 : 0.7,
      ease: 'power3.out',
      stagger: { amount: masked ? 0.28 : 0.38 },
      onComplete: () => split.revert(),
    });
  };

  const initMotion = () => {
    const { gsap, ScrollTrigger, SplitText } = window;
    if (!gsap || !ScrollTrigger || !SplitText) {
      revealTitles();
      return;
    }
    gsap.registerPlugin(ScrollTrigger, SplitText);
    ScrollTrigger.config({ ignoreMobileResize: true });
    const mm = gsap.matchMedia();

    mm.add('(prefers-reduced-motion: reduce)', revealTitles);

    mm.add('(prefers-reduced-motion: no-preference)', () => {
      document.querySelectorAll('[data-split-intro]').forEach((el) => introTitle(gsap, SplitText, el));

      // ヒーロー: 読み込み時にわずかに引き、スクロールに合わせて拡大・移動（transform のみ）
      const hero = document.querySelector('[data-hero]');
      if (hero) {
        gsap.from('[data-hero-image]', { scale: 1.1, duration: 1.2, ease: 'power2.out' });
        gsap.from('[data-hero-rule]', { scaleX: 0, duration: 1.1, ease: 'power3.inOut', delay: 0.1 });
        gsap.to('.p-hero__media', {
          yPercent: 12,
          scale: 1.08,
          ease: 'none',
          scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
        });
      }

      // 画像のパララックス（枠の中で上下にずらす）
      gsap.utils.toArray('[data-parallax]').forEach((img) => {
        gsap.fromTo(img, { yPercent: -5 }, {
          yPercent: 5,
          ease: 'none',
          scrollTrigger: { trigger: img.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
        });
      });

      // 大きな数字をわずかに遅らせて動かす
      gsap.utils.toArray('[data-drift]').forEach((el) => {
        gsap.fromTo(el, { yPercent: 14 }, {
          yPercent: -14,
          ease: 'none',
          scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true },
        });
      });
    });

    // 施術の流れ: PC かつ動きを減らす設定でないときだけ、縦のリストを横スクロールの演出に切り替える
    mm.add('(min-width: 64em) and (prefers-reduced-motion: no-preference)', () => {
      const section = document.querySelector('[data-flow]');
      if (!section) return undefined;
      const pin = section.querySelector('[data-flow-pin]');
      const viewport = section.querySelector('[data-flow-viewport]');
      const track = section.querySelector('[data-flow-track]');
      const bar = section.querySelector('[data-flow-progress]');
      const header = document.querySelector('[data-header]');
      section.classList.add('is-horizontal');

      const distance = () => Math.max(0, track.scrollWidth - viewport.clientWidth);
      const timeline = gsap.timeline({
        scrollTrigger: {
          trigger: pin,
          start: () => `top ${header ? header.offsetHeight : 0}px`,
          end: () => `+=${distance()}`,
          pin: true,
          scrub: 0.6,
          anticipatePin: 1,
          invalidateOnRefresh: true,
        },
      });
      timeline.to(track, { x: () => -distance(), ease: 'none' }, 0);
      if (bar) timeline.fromTo(bar, { scaleX: 0 }, { scaleX: 1, ease: 'none' }, 0);

      return () => section.classList.remove('is-horizontal');
    });
  };

  /* --------------------------------------------------------------------- */
  initHeader();
  initDrawer();
  initTelNotice();
  initToc();
  initSubmitOnce();
  initErrorLinks();
  initDemoForm();
  initMotion();
  focusOnLoad();
})();
