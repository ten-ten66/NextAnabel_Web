/**
 * 白磁スキンクリニック 医療脱毛LP
 *
 * - 料金シミュレーター（人体図 ⇄ 部位の一覧を双方向に同期し、税込総額・回数の目安を表示）
 * - 固定CTA（ファーストビューを過ぎたら表示し、予約フォームと試算結果が見えている間は隠す）
 * - 予約フォーム（入力 → 確認 → 完了。サーバー版は api/reserve.php に JSON で送信、静的版は送信しない）
 * - しくみの図の演出、よくある質問のアコーディオン、計測用のイベント（dataLayer）
 *
 * JavaScript が動かなくても本文はすべて読める（チェックボックス・料金表・FAQ の回答は HTML のまま）。
 */
'use strict';

(() => {
  const root = document.documentElement;
  const isStatic = root.dataset.env === 'static';
  const isDemo = root.dataset.build === 'demo';
  const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const reduceMotion = () => motionQuery.matches;
  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
  const numberFormat = new Intl.NumberFormat('ja-JP');
  const yen = (amount) => `${numberFormat.format(amount)}円`;
  const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

  /* ---------------------------------------------------------------------
     計測: GTM などのタグは読み込まず、dataLayer にイベントを積むだけにする
     --------------------------------------------------------------------- */
  window.dataLayer = window.dataLayer || [];
  const track = (event, params = {}) => {
    window.dataLayer.push({ event, ...params });
  };

  /* ---------------------------------------------------------------------
     通知（role="status" の領域）
     --------------------------------------------------------------------- */
  const toast = $('.js-toast');
  let toastTimer = 0;
  let toastClearTimer = 0;
  const showToast = (message) => {
    if (!toast) return;
    clearTimeout(toastTimer);
    clearTimeout(toastClearTimer);
    toast.textContent = message;
    toast.classList.add('is-visible');
    toastTimer = setTimeout(() => {
      toast.classList.remove('is-visible');
      // 同じ文言を続けて出したときも読み上げられるよう、消えたあとに空にする
      toastClearTimer = setTimeout(() => { toast.textContent = ''; }, 400);
    }, 3200);
  };

  document.addEventListener('click', (event) => {
    const cta = event.target.closest('[data-cta]');
    if (cta) track('cta_click', { location: cta.dataset.cta });

    // 公開デモでは電話をかけず、通知だけを出す（GUIDELINES 5章）
    if (isDemo) {
      const tel = event.target.closest('a[href^="tel:"]');
      if (tel) {
        event.preventDefault();
        showToast('サンプルサイトのため、電話はかかりません。');
      }
    }
  });

  const scrollToElement = (element) => {
    element.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'start' });
  };

  /* ---------------------------------------------------------------------
     料金シミュレーター
     --------------------------------------------------------------------- */
  const initSimulator = () => {
    const sim = $('.js-sim');
    const dataElement = $('#plan-data');
    if (!sim || !dataElement) return null;

    let plan;
    try {
      plan = JSON.parse(dataElement.textContent);
    } catch (error) {
      return null;
    }

    const parts = new Map(plan.parts.map((part) => [part.id, part]));
    const order = plan.parts.map((part) => part.id);
    const shortName = (id) => parts.get(id).label.split('（')[0];
    const selected = new Set();
    const listeners = [];

    const checks = $$('.js-sim-check', sim);
    const regions = $$('.js-map-part', sim);
    const mirrors = $$('.js-map-mirror', sim);
    const views = $$('.js-sim-view', sim);
    const viewButtons = $$('.js-sim-view-btn', sim);
    const totalElement = $('.js-sim-total', sim);
    const totalWrap = $('.js-sim-total-wrap', sim);
    const live = $('.js-sim-live', sim);
    const selectedText = $('.js-sim-selected', sim);
    const setNote = $('.js-sim-set-note', sim);
    const setLabel = $('.js-sim-set-label', sim);
    const once = $('.js-sim-once', sim);
    const onceUnit = $('.js-sim-once-unit', sim);
    const coolingOff = $('.js-sim-coolingoff', sim);
    const readout = $('.js-sim-readout', sim);
    const reserveLink = $('.js-sim-reserve', sim);
    const defaultReadout = readout ? readout.textContent : '';

    const sum = (ids, key) => ids.reduce((total, id) => total + parts.get(id)[key], 0);

    /**
     * 試算（lib/plan.php の lp_estimate() と同じ計算）
     * セットに含まれる部位をすべて選んだときは「セット料金＋残りの部位」を候補にし、総額がもっとも低いものを採用する。
     */
    const estimate = (input) => {
      const ids = order.filter((id) => input.includes(id));
      let best = { course: sum(ids, 'course'), once: sum(ids, 'once'), set: null, rest: ids };
      plan.sets.forEach((set) => {
        if (!set.includes.every((id) => ids.includes(id))) return;
        const rest = ids.filter((id) => !set.includes.includes(id));
        const course = set.course + sum(rest, 'course');
        if (course < best.course) {
          best = { course, once: set.once + sum(rest, 'once'), set, rest };
        }
      });
      return { ...best, ids, count: ids.length };
    };

    const qualifies = (amount) => amount > plan.coolingOff.amount && plan.course.months > plan.coolingOff.months;

    // 総額のカウントアップ（表示だけ。読み上げは確定値を live 領域で1回だけ）
    let shown = 0;
    let frame = 0;
    const rollTo = (target) => {
      cancelAnimationFrame(frame);
      const from = shown;
      if (reduceMotion() || from === target) {
        shown = target;
        totalElement.textContent = numberFormat.format(target);
        return;
      }
      const duration = 650;
      const start = performance.now();
      if (totalElement.animate) {
        totalElement.animate(
          [{ transform: 'translateY(6px)', opacity: 0.55 }, { transform: 'translateY(0)', opacity: 1 }],
          { duration: 320, easing: 'cubic-bezier(.22,1,.36,1)' }
        );
      }
      const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - (1 - t) ** 3;
        shown = t < 1 ? Math.round((from + (target - from) * eased) / 100) * 100 : target;
        totalElement.textContent = numberFormat.format(shown);
        if (t < 1) frame = requestAnimationFrame(step);
      };
      frame = requestAnimationFrame(step);
    };

    let liveTimer = 0;
    let trackTimer = 0;
    let result = estimate([]);
    let enhanced = false;

    const render = (source) => {
      result = estimate([...selected]);

      checks.forEach((check) => { check.checked = selected.has(check.value); });
      regions.forEach((region) => {
        const on = selected.has(region.dataset.part);
        region.classList.toggle('is-selected', on);
        if (enhanced) region.setAttribute('aria-pressed', String(on));
      });
      mirrors.forEach((mirror) => mirror.classList.toggle('is-selected', selected.has(mirror.dataset.part)));
      viewButtons.forEach((button) => {
        const count = result.ids.filter((id) => parts.get(id).side === button.dataset.view).length;
        const badge = $('.js-sim-side-count', button);
        if (badge) badge.textContent = count ? String(count) : '';
        const label = button.dataset.view === 'back' ? '背面' : '前面';
        if (count) button.setAttribute('aria-label', `${label}（${count}部位を選択中）`);
        else button.removeAttribute('aria-label');
      });

      if (result.count) {
        selectedText.textContent = '';
        const strong = document.createElement('strong');
        strong.textContent = `${result.count}部位`;
        selectedText.append(strong, `を選択中：${result.ids.map(shortName).join('、')}`);
      } else {
        selectedText.textContent = '部位が選ばれていません';
      }

      rollTo(result.course);
      totalWrap.classList.toggle('is-empty', result.count === 0);
      once.textContent = result.count ? numberFormat.format(result.once) : '—';
      onceUnit.hidden = result.count === 0;

      if (result.set) {
        setLabel.textContent = result.rest.length
          ? `${result.set.label}＋${result.rest.map(shortName).join('・')}`
          : result.set.label;
        setNote.hidden = false;
      } else {
        setNote.hidden = true;
      }
      coolingOff.hidden = !(result.count && qualifies(result.course));

      if (source !== 'init') {
        clearTimeout(liveTimer);
        liveTimer = setTimeout(() => {
          live.textContent = result.count
            ? `${result.count}部位を選択中。${plan.course.count}回コース総額 ${yen(result.course)}（税込）${result.set ? '、セット料金で計算しています' : ''}。`
            : '部位の選択をすべて解除しました。';
        }, 700);

        clearTimeout(trackTimer);
        trackTimer = setTimeout(() => {
          track('simulator_change', {
            parts_count: result.count,
            total: result.course,
            set_id: result.set ? result.set.id : null,
          });
        }, 500);
      }

      listeners.forEach((listener) => listener(result));
    };

    const toggle = (id) => {
      if (!parts.has(id)) return;
      if (selected.has(id)) selected.delete(id);
      else selected.add(id);
      render('toggle');
    };

    const pop = (element) => {
      if (reduceMotion() || !element.animate) return;
      element.animate(
        [{ transform: 'scale(1)' }, { transform: 'scale(1.06)' }, { transform: 'scale(1)' }],
        { duration: 280, easing: 'ease-out' }
      );
    };

    const setReadout = (id) => {
      if (!readout) return;
      if (!id) {
        readout.textContent = defaultReadout;
        readout.classList.remove('is-active');
        return;
      }
      const part = parts.get(id);
      readout.textContent = `${part.label}　${plan.course.count}回 ${yen(part.course)}（税込）${selected.has(id) ? '・選択中' : ''}`;
      readout.classList.add('is-active');
    };

    // 前面・背面の切り替え（裏返すように横方向に縮めて入れ替える）
    let flip = 0;
    const showView = (side, animate) => {
      viewButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === side)));
      views.forEach((view) => { if (view.getAnimations) view.getAnimations().forEach((animation) => animation.cancel()); });
      const next = views.find((view) => view.dataset.view === side);
      const current = views.find((view) => !view.hidden && view !== next);
      const token = ++flip;
      if (!animate || reduceMotion() || !current || !current.animate) {
        views.forEach((view) => { view.hidden = view !== next; });
        return;
      }
      const out = current.animate(
        [{ transform: 'scaleX(1)', opacity: 1 }, { transform: 'scaleX(0.04)', opacity: 0.3 }],
        { duration: 170, easing: 'ease-in' }
      );
      out.onfinish = () => {
        if (token !== flip) return;
        views.forEach((view) => { view.hidden = view !== next; });
        next.animate(
          [{ transform: 'scaleX(0.04)', opacity: 0.3 }, { transform: 'scaleX(1)', opacity: 1 }],
          { duration: 240, easing: 'cubic-bezier(.22,1,.36,1)' }
        );
      };
    };

    // JavaScript が動くときだけ、人体図をボタンとして使えるようにする
    const enhance = () => {
      enhanced = true;
      sim.classList.add('is-enhanced');
      $$('.js-sim-toggle, .js-sim-sets', sim).forEach((element) => { element.hidden = false; });
      if (readout) readout.hidden = false;
      $$('.js-map', sim).forEach((svg) => {
        svg.removeAttribute('aria-hidden');
        svg.setAttribute('role', 'group');
        svg.setAttribute('aria-label', svg.dataset.side === 'back' ? '人体図（背面）' : '人体図（前面）');
      });
      mirrors.forEach((mirror) => mirror.setAttribute('aria-hidden', 'true'));
      $$('.js-map-deco', sim).forEach((deco) => deco.setAttribute('aria-hidden', 'true'));
      regions.forEach((region) => {
        region.setAttribute('role', 'button');
        region.setAttribute('tabindex', '0');
        region.setAttribute('aria-pressed', 'false');
        region.setAttribute('aria-label', region.dataset.label);
      });
      showView('front', false);
    };

    enhance();

    regions.forEach((region) => {
      const id = region.dataset.part;
      region.addEventListener('click', () => {
        toggle(id);
        pop(region);
        setReadout(id);
      });
      region.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        toggle(id);
        pop(region);
        setReadout(id);
      });
      region.addEventListener('pointerenter', () => setReadout(id));
      region.addEventListener('focus', () => setReadout(id));
      region.addEventListener('pointerleave', () => setReadout(null));
      region.addEventListener('blur', () => setReadout(null));
    });

    checks.forEach((check) => {
      check.addEventListener('change', () => {
        if (check.checked) selected.add(check.value);
        else selected.delete(check.value);
        render('list');
      });
    });

    viewButtons.forEach((button) => {
      button.addEventListener('click', () => {
        if (button.getAttribute('aria-pressed') === 'true') return;
        showView(button.dataset.view, true);
      });
    });

    $$('.js-sim-set', sim).forEach((button) => {
      button.addEventListener('click', () => {
        const set = plan.sets.find((item) => item.id === button.dataset.set);
        if (!set) return;
        set.includes.forEach((id) => selected.add(id));
        render('set');
      });
    });

    const clear = $('.js-sim-clear', sim);
    if (clear) {
      clear.addEventListener('click', () => {
        selected.clear();
        render('clear');
      });
    }

    // ブラウザが前回のチェック状態を復元した場合に合わせる
    checks.forEach((check) => { if (check.checked) selected.add(check.value); });
    render('init');

    let reserveHandler = null;
    if (reserveLink) {
      reserveLink.addEventListener('click', (event) => {
        if (!reserveHandler) return;
        event.preventDefault();
        reserveHandler(result);
      });
    }

    return {
      estimate,
      courseCount: plan.course.count,
      onChange: (listener) => { listeners.push(listener); listener(result); },
      onReserve: (handler) => { reserveHandler = handler; },
    };
  };

  /* ---------------------------------------------------------------------
     固定CTA
     --------------------------------------------------------------------- */
  const initDock = (sim) => {
    const dock = $('.js-dock');
    if (!dock || !('IntersectionObserver' in window)) return;
    const watched = new Map([
      [$('.js-fv'), 'fv'],
      [$('#reserve'), 'reserve'],
      [$('.js-sim-result'), 'result'],
    ]);
    watched.delete(null);
    const visible = { fv: true, reserve: false, result: false };

    const apply = () => {
      const show = !visible.fv && !visible.reserve && !visible.result;
      dock.classList.toggle('is-hidden', !show);
      dock.inert = !show;
      if (show) dock.removeAttribute('aria-hidden');
      else dock.setAttribute('aria-hidden', 'true');
    };

    dock.classList.add('is-hidden');
    dock.hidden = false;
    apply();

    // 上端にちょうど接しているだけの要素（スクロールし終えた FV など）は「見えていない」とみなす
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => { visible[watched.get(entry.target)] = entry.isIntersecting; });
      apply();
    }, { rootMargin: '-1px 0px 0px 0px' });
    watched.forEach((_, element) => observer.observe(element));

    const summary = $('.js-dock-summary', dock);
    const partsText = $('.js-dock-parts', dock);
    const totalText = $('.js-dock-total', dock);
    if (sim && summary) {
      sim.onChange((result) => {
        summary.hidden = result.count === 0;
        partsText.textContent = `${result.count}部位`;
        totalText.textContent = yen(result.course);
      });
    }
  };

  /* ---------------------------------------------------------------------
     しくみの図: 画面に入ったときに1回だけ再生する（止まった状態が完成形）
     --------------------------------------------------------------------- */
  const initDiagram = () => {
    const diagram = $('.js-diagram');
    if (!diagram || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver((entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) return;
      observer.disconnect();
      if (!reduceMotion()) diagram.classList.add('is-play');
    }, { threshold: 0.45 });
    observer.observe(diagram);
  };

  /* ---------------------------------------------------------------------
     よくある質問（HTML では開いた状態。ここで閉じ、ページ内検索で見つかったら開く）
     --------------------------------------------------------------------- */
  const initFaq = () => {
    $$('.js-faq-toggle').forEach((button) => {
      const panel = document.getElementById(button.getAttribute('aria-controls'));
      if (!panel) return;
      const set = (open) => {
        button.setAttribute('aria-expanded', String(open));
        if (open) panel.removeAttribute('hidden');
        else panel.setAttribute('hidden', 'until-found');
      };
      set(false);
      button.addEventListener('click', () => set(button.getAttribute('aria-expanded') !== 'true'));
      panel.addEventListener('beforematch', () => set(true));
    });
  };

  /* ---------------------------------------------------------------------
     予約フォーム（入力 → 確認 → 完了）
     --------------------------------------------------------------------- */
  const initForm = () => {
    const form = $('.js-reserve-form');
    if (!form) return null;

    const confirmView = $('.js-confirm');
    const completeView = $('.js-complete');
    const heading = $('.js-reserve-heading');
    const steps = $$('.js-steps > li');
    const summary = $('.js-error-summary', form);
    const summaryTitle = $('.js-error-summary-title', form);
    const summaryList = $('.js-error-summary-list', form);
    const submitButton = $('.js-submit', form);
    const sendButton = $('.js-send');
    const sendLabel = $('.js-send-label');
    const backButton = $('.js-back');
    const sendError = $('.js-send-error');
    const prefillNote = $('.js-prefill-note');
    const prefillText = $('.js-prefill-text');
    const estimateText = $('.js-confirm-estimate');
    const partChecks = $$('.js-form-part', form);
    const control = (name) => form.elements.namedItem(name);

    form.noValidate = true;
    submitButton.disabled = false;

    // 日付の選択範囲は閲覧時点の日付で決める（静的版の HTML はビルド時点で固定されるため）。
    // 「今日」はクリニックの所在地（Asia/Tokyo）の日付で数え、サーバー側の検証と食い違わないようにする
    const minDays = Number(form.dataset.minDays || 1);
    const maxDays = Number(form.dataset.maxDays || 60);
    const closedDays = (form.dataset.closedDays || '').split(',').filter(Boolean).map(Number);
    const clinicToday = () => {
      try {
        const ymd = new Intl.DateTimeFormat('en-CA', {
          timeZone: form.dataset.timezone || 'Asia/Tokyo', year: 'numeric', month: '2-digit', day: '2-digit',
        }).format(new Date());
        const [y, m, d] = ymd.split('-').map(Number);
        if (y && m && d) return new Date(y, m - 1, d);
      } catch (error) { /* タイムゾーン未対応の環境では端末の日付を使う */ }
      const local = new Date();
      local.setHours(0, 0, 0, 0);
      return local;
    };
    const today = clinicToday();
    const addDays = (date, days) => {
      const copy = new Date(date);
      copy.setDate(copy.getDate() + days);
      return copy;
    };
    const pad = (n) => String(n).padStart(2, '0');
    const toYmd = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const parseYmd = (value) => {
      const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
      if (!match) return null;
      const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
      return toYmd(date) === value ? date : null;
    };
    const minDate = addDays(today, minDays);
    const maxDate = addDays(today, maxDays);
    control('date1').min = toYmd(minDate);
    control('date1').max = toYmd(maxDate);

    // 全角の英数字・記号を半角に（電話番号・メールアドレス）
    const toHalfWidth = (value) => value
      .replace(/[！-～]/g, (char) => String.fromCharCode(char.charCodeAt(0) - 0xfee0))
      .replace(/　/g, ' ');
    const normalize = {
      tel: (value) => toHalfWidth(value).replace(/[‐‑‒–—―ーｰ−]/g, '-').replace(/\s+/g, ''),
      email: (value) => toHalfWidth(value).trim(),
    };

    const valueOf = (name) => {
      if (name === 'parts') return partChecks.filter((check) => check.checked).map((check) => check.value);
      if (name === 'time1') {
        const picked = $$('input[name="time1"]', form).find((radio) => radio.checked);
        return picked ? picked.value : '';
      }
      if (name === 'consent') return control('consent').checked ? '1' : '';
      return control(name).value;
    };

    const length = (value) => [...value].length;

    // サーバー側（core/form/Validator.php と lib/reservation.php）と同じ規則・文言
    const rules = {
      name: (value) => {
        const text = value.trim();
        if (!text) return 'お名前を入力してください。';
        return length(text) > 50 ? 'お名前は50文字以内で入力してください。' : '';
      },
      tel: (value) => {
        if (!value) return '電話番号を入力してください。';
        const digits = value.replace(/\D/g, '');
        return (/[^\d-]/.test(value) || !/^0\d{9,10}$/.test(digits)) ? '電話番号は半角数字とハイフンで入力してください。' : '';
      },
      email: (value) => {
        if (!value) return 'メールアドレスを入力してください。';
        if (length(value) > 254) return 'メールアドレスは254文字以内で入力してください。';
        return /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/.test(value) ? '' : 'メールアドレスの形式が正しくありません。';
      },
      parts: () => '',
      date1: (value) => {
        if (!value) return '第1希望日を選択してください。';
        const date = parseYmd(value);
        if (!date) return '第1希望日の形式が正しくありません。';
        if (date < minDate) return minDays === 1 ? '第1希望日は明日以降の日付を選択してください。' : `第1希望日は${minDays}日後以降の日付を選択してください。`;
        if (date > maxDate) return `第1希望日は${maxDays}日以内の日付を選択してください。`;
        if (closedDays.includes(date.getDay())) return `${WEEKDAYS[date.getDay()]}曜日は休診日です。別の日を選択してください。`;
        return '';
      },
      time1: (value) => (value ? '' : 'ご希望の時間帯を選択してください。'),
      message: (value) => (length(value) > 500 ? 'ご質問などは500文字以内で入力してください。' : ''),
      consent: (value) => (value === '1' ? '' : '個人情報の取り扱いへの同意が必要です。内容をご確認のうえ、チェックを入れてください。'),
    };
    const names = Object.keys(rules);
    const errors = {};
    const touched = new Set();

    const fieldOf = (name) => form.querySelector(`.c-field[data-field="${name}"]`);
    const firstControl = (name) => $('.js-field', fieldOf(name));

    const setError = (name, message) => {
      errors[name] = message;
      const error = document.getElementById(`f-${name}-error`);
      if (!error) return;
      error.textContent = message;
      error.hidden = !message;
      $$('.js-field', fieldOf(name)).forEach((input) => {
        const described = new Set((input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        if (message) {
          input.setAttribute('aria-invalid', 'true');
          described.add(error.id);
        } else {
          input.removeAttribute('aria-invalid');
          described.delete(error.id);
        }
        if (described.size) input.setAttribute('aria-describedby', [...described].join(' '));
        else input.removeAttribute('aria-describedby');
        if (input.classList.contains('c-field__input')) input.classList.toggle('is-valid', !message && input.value !== '');
      });
    };

    const validate = (name) => {
      const message = rules[name](valueOf(name));
      setError(name, message);
      return message;
    };

    const hideSummary = () => { summary.hidden = true; };
    const showSummary = (failed) => {
      summaryList.replaceChildren(...failed.map((name) => {
        const item = document.createElement('li');
        const link = document.createElement('a');
        const target = firstControl(name);
        link.href = `#${target.id}`;
        link.textContent = errors[name];
        link.addEventListener('click', (event) => {
          event.preventDefault();
          target.focus();
        });
        item.append(link);
        return item;
      }));
      summaryTitle.textContent = `入力内容をご確認ください（${failed.length}件）`;
      summary.hidden = false;
      summary.focus();
    };

    let started = false;
    const markStarted = () => {
      if (started) return;
      started = true;
      track('form_start');
    };

    $$('.js-field', form).forEach((input) => {
      const name = input.name === 'parts[]' ? 'parts' : input.name;
      input.addEventListener('input', () => {
        markStarted();
        touched.add(name);
        if (errors[name]) validate(name);
      });
      input.addEventListener('change', () => {
        markStarted();
        touched.add(name);
        if (['date1', 'time1', 'consent'].includes(name) || errors[name]) validate(name);
      });
      input.addEventListener('blur', () => {
        if (normalize[name]) input.value = normalize[name](input.value);
        if (['parts', 'time1', 'consent'].includes(name)) return;
        if (touched.has(name) || input.value !== '') validate(name);
      });
    });

    const fullLabel = (id) => {
      const check = partChecks.find((item) => item.value === id);
      return check ? check.dataset.fullLabel : id;
    };
    const slotLabel = (value) => {
      const radio = $$('input[name="time1"]', form).find((item) => item.value === value);
      return radio ? radio.parentElement.textContent.trim() : '';
    };
    const formatDate = (value) => {
      const date = parseYmd(value);
      return date ? `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日（${WEEKDAYS[date.getDay()]}）` : value;
    };

    let estimator = null;
    let courseCount = 5;
    const setConfirm = (name, text) => {
      const target = confirmView.querySelector(`.js-confirm-value[data-field="${name}"]`);
      if (target) target.textContent = text;
    };
    const fillConfirm = () => {
      const parts = valueOf('parts');
      setConfirm('name', valueOf('name').trim());
      setConfirm('tel', valueOf('tel'));
      setConfirm('email', valueOf('email'));
      setConfirm('parts', parts.length ? parts.map(fullLabel).join('、') : '未選択（カウンセリングで相談）');
      setConfirm('datetime', `${formatDate(valueOf('date1'))}　${slotLabel(valueOf('time1'))}`);
      setConfirm('message', valueOf('message').trim() || 'なし');
      const result = estimator && parts.length ? estimator(parts) : null;
      estimateText.hidden = !result;
      if (result) {
        estimateText.textContent = `（参考）この部位の${courseCount}回コース総額 ${yen(result.course)}（税込）${result.set ? '・セット料金で計算' : ''}`;
      }
    };

    // 画面の切り替えとフォーカス移動
    const order = ['input', 'confirm', 'complete'];
    const panels = { input: form, confirm: confirmView, complete: completeView };
    let step = 'input';
    const go = (next) => {
      step = next;
      Object.entries(panels).forEach(([key, panel]) => { panel.hidden = key !== next; });
      if (next !== 'input') prefillNote.hidden = true;
      const index = order.indexOf(next);
      steps.forEach((item, i) => {
        item.classList.toggle('is-current', i === index);
        item.classList.toggle('is-done', i < index);
        if (i === index) item.setAttribute('aria-current', 'step');
        else item.removeAttribute('aria-current');
      });
      const title = next === 'input' ? heading : $('.js-step-title', panels[next]);
      scrollToElement(next === 'input' ? heading : form.closest('.p-reserve__card'));
      title.focus({ preventScroll: true });
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      ['tel', 'email'].forEach((name) => { control(name).value = normalize[name](control(name).value); });
      const failed = names.filter((name) => validate(name));
      if (failed.length) {
        showSummary(failed);
        return;
      }
      hideSummary();
      fillConfirm();
      sendError.hidden = true;
      go('confirm');
    });

    backButton.addEventListener('click', () => go('input'));

    const finish = () => {
      track('form_submit', { mode: isStatic ? 'static' : 'server', parts_count: valueOf('parts').length });
      go('complete');
    };

    const showSendError = (message) => {
      sendError.textContent = message;
      sendError.hidden = false;
    };
    const SEND_ERRORS = {
      403: '一定時間が経過したため、送信内容を確認できませんでした。お手数ですが、ページを再読み込みしてからもう一度お試しください。',
      429: '短時間に続けて送信されました。しばらく時間をおいてから、もう一度お試しください。',
      default: '送信できませんでした。時間をおいてもう一度お試しいただくか、お電話でご予約ください。',
    };

    let sending = false;
    sendButton.addEventListener('click', async () => {
      if (sending) return;
      sendError.hidden = true;

      // 静的版（GitHub Pages・Artifact）では送信せず、完了画面だけを表示する
      if (isStatic || !form.dataset.endpoint) {
        finish();
        return;
      }

      sending = true;
      sendButton.disabled = true;
      backButton.disabled = true;
      sendButton.setAttribute('aria-busy', 'true');
      sendLabel.textContent = '送信しています…';
      const tokenMeta = $('meta[name="csrf-token"]');
      const payload = {
        _token: tokenMeta ? tokenMeta.content : '',
        name: valueOf('name').trim(),
        tel: valueOf('tel'),
        email: valueOf('email'),
        parts: valueOf('parts'),
        date1: valueOf('date1'),
        time1: valueOf('time1'),
        message: valueOf('message'),
        consent: valueOf('consent'),
        website: control('website') ? control('website').value : '',
      };
      try {
        const response = await fetch(form.dataset.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (response.ok && data.ok) {
          finish();
        } else if (response.status === 422 && data.errors) {
          // 文言は画面側の規則を優先する（同じ文言。Webフォントのサブセットに含まれる文字で表示するため）
          const failed = names.filter((name) => data.errors[name]);
          failed.forEach((name) => setError(name, rules[name](valueOf(name)) || data.errors[name]));
          go('input');
          if (failed.length) showSummary(failed);
        } else {
          showSendError(SEND_ERRORS[response.status] || SEND_ERRORS.default);
        }
      } catch (error) {
        showSendError('通信できませんでした。電波の良い場所で、もう一度お試しください。');
      } finally {
        sending = false;
        sendButton.disabled = false;
        backButton.disabled = false;
        sendButton.removeAttribute('aria-busy');
        sendLabel.textContent = 'この内容で申し込む';
      }
    });

    return {
      useEstimator: (fn, count) => { estimator = fn; courseCount = count || courseCount; },
      // シミュレーターで選んだ部位を「希望部位」に入れて、フォームへ移動する
      prefill: (result) => {
        if (step === 'complete') {
          form.reset();
          names.forEach((name) => setError(name, ''));
          hideSummary();
        }
        partChecks.forEach((check) => { check.checked = result.ids.includes(check.value); });
        if (step !== 'input') go('input');
        else {
          scrollToElement(heading);
          heading.focus({ preventScroll: true });
        }
        prefillNote.hidden = result.count === 0;
        if (result.count) {
          prefillText.textContent = `シミュレーターで選んだ${result.count}部位を、希望部位に入力しました。変更もできます。`;
        }
      },
    };
  };

  const simulator = initSimulator();
  initDock(simulator);
  initDiagram();
  initFaq();
  const reservation = initForm();
  if (simulator && reservation) {
    reservation.useEstimator(simulator.estimate, simulator.courseCount);
    simulator.onReserve(reservation.prefill);
  }
})();
