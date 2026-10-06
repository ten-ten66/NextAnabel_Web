/**
 * 白磁スキンクリニック（架空）: 予約フォーム（入力の補助と、静的版の送信デモ）
 * main.js の後に読み込み、window.Hakuji の補助関数を使う。
 */
'use strict';

(() => {
  const H = window.Hakuji;
  if (!H) return;
  const { $, $$, create, WEEK, tokyoNow, ymd, addDays, jaDate, readCalendar, slotFor } = H;

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
      // 定休日（木曜）は送信時にエラーになるため先に知らせる。祝日はサーバーでは判定しないため、ご提案の案内にとどめる
      const warn = () => {
        const field = input.closest('.c-field');
        field.querySelector('.js-closed-warning')?.remove();
        if (!calendar || field.querySelector('.c-field__error') || !/^\d{4}-\d{2}-\d{2}$/.test(input.value)) return;
        const [y, m, d] = input.value.split('-').map(Number);
        const date = new Date(Date.UTC(y, m - 1, d));
        if (slotFor(calendar, date)) return;
        const weekdayClosed = !calendar.schedule[date.getUTCDay()];
        input.after(create('p', 'c-field__warning js-closed-warning', weekdayClosed
          ? `${WEEK[date.getUTCDay()]}曜日は休診日です。別の日を選択してください。`
          : '選択された日は祝日のため休診です。この日をご希望の場合は、近い日程をご提案します。'));
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

    if (form.hasAttribute('data-demo')) initDemoForm(form, { minDate, maxDate, calendar });
  };

  /* 静的版の送信デモ（サーバーの検証 core/form/Validator.php と同じ規則・同じ文言） */
  const initDemoForm = (form, { minDate, maxDate, calendar }) => {
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
          add(field, `${labels[field]}は明日以降の日付を選択してください。`);
        } else if (value > maxDate) {
          add(field, `${labels[field]}は60日以内の日付を選択してください。`);
        } else if (calendar) {
          const [y, m, d] = value.split('-').map(Number);
          if (!calendar.schedule[new Date(Date.UTC(y, m - 1, d)).getUTCDay()]) {
            add(field, `${labels[field]}は休診日（${form.dataset.closedLabel || '休診日'}）以外の日付を選択してください。`);
          }
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
        field.querySelector('.js-closed-warning')?.remove();
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

  initForm();
})();
