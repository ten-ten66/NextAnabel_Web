/**
 * 白磁スキンクリニック（架空）: トップページ（受付状況のリング・ファーストビューの「釉薬のゆらぎ」）
 * main.js の後に読み込み、window.Hakuji の補助関数を使う。
 */
'use strict';

(() => {
  const H = window.Hakuji;
  if (!H) return;
  const { root, motionQuery, reduceMotion, $, WEEK, tokyoNow, toMinutes, fromMinutes, jaDate, readCalendar, slotFor, nextOpenDay } = H;

  // 本日の受付状況（SVG のリング）。site.php の診療時間から計算し、1分ごとに更新する
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
            detail: `本日の受付終了まで${remain}分です（最終受付\u00a0${fromMinutes(last)}）。${closing ? 'お急ぎの方はお電話ください。' : ''}`,
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

  // 背景の動きは、最初の描画（FCP）が画面に表示されてから始める。
  // 描画の直後に Canvas とフェードイン（1.2秒）を始めると、最初の画面の表示がフェードの終わりまで遅れることがあった
  // （Lighthouse の計測で FCP が約1.28秒に固定される現象。表示後に始めるようにしてから発生していない）
  const afterFirstContentfulPaint = (callback) => {
    let done = false;
    const run = () => {
      if (done) return;
      done = true;
      callback();
    };
    if (!('PerformanceObserver' in window) || !(PerformanceObserver.supportedEntryTypes || []).includes('paint')) {
      window.setTimeout(run, 0);
      return;
    }
    const observer = new PerformanceObserver((list) => {
      if (!list.getEntriesByName('first-contentful-paint').length) return;
      observer.disconnect();
      window.setTimeout(run, 0);
    });
    observer.observe({ type: 'paint', buffered: true });
    window.setTimeout(run, 3000); // 計測できない場合でも3秒後には始める
  };

  // 受付状況は最初の描画のあとに、背景の演出は最初の描画が表示されてから始める
  window.requestAnimationFrame(() => window.setTimeout(initReception, 0));
  afterFirstContentfulPaint(initGlaze);
})();
