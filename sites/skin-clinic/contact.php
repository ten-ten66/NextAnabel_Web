<?php
require __DIR__ . '/_init.php';

use Core\Form\FormFlow;
use Core\Form\Mail;
use Core\Form\MailerFactory;

$menuOptions = [];
foreach (treatments() as $t) {
    $menuOptions[$t['slug']] = $t['name'];
}
$menuOptions['undecided'] = 'まだ決めていない・相談したい';

$fields = [
    'name' => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'max' => 40],
    'kana' => ['label' => 'フリガナ', 'type' => 'kana', 'required' => true, 'max' => 60],
    'email' => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true, 'max' => 254],
    'tel' => ['label' => '電話番号', 'type' => 'tel', 'required' => true],
    'purpose' => [
        'label' => 'ご用件',
        'type' => 'choice',
        'required' => true,
        'options' => [
            'counseling' => 'カウンセリング予約',
            'treatment' => '施術の予約',
            'other' => 'その他のお問い合わせ',
        ],
    ],
    'menu' => ['label' => '気になる施術', 'type' => 'choice', 'required' => false, 'options' => $menuOptions],
    // 定休日（木曜）と、site.php の closed_dates（祝日・年末年始）は Validator が受け付けない
    'date1' => [
        'label' => '第1希望日',
        'type' => 'date',
        'required' => true,
        'min_days' => 1,
        'max_days' => 60,
        'closed_weekdays' => closed_weekdays(),
        'closed_dates' => (array) site('closed_dates', []),
        'closed_label' => (string) site('closed_label'),
    ],
    'time' => [
        'label' => '時間帯',
        'type' => 'choice',
        'required' => true,
        'options' => [
            'am' => '10:00〜12:00',
            'midday' => '12:00〜15:00',
            'pm' => '15:00〜18:30',
            'any' => '指定なし',
        ],
    ],
    'date2' => [
        'label' => '第2希望日',
        'type' => 'date',
        'required' => false,
        'min_days' => 1,
        'max_days' => 60,
        'closed_weekdays' => closed_weekdays(),
        'closed_dates' => (array) site('closed_dates', []),
        'closed_label' => (string) site('closed_label'),
    ],
    'message' => ['label' => 'ご相談内容', 'type' => 'textarea', 'required' => false, 'max' => 1000],
    'consent' => [
        'label' => '個人情報の取り扱い',
        'type' => 'consent',
        'required' => true,
        'message' => 'プライバシーポリシーへの同意が必要です。',
    ],
];

// 「その他のお問い合わせ」は来院日を伴わないため、希望日と時間帯を任意にする
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['purpose'] ?? '') === 'other') {
    $fields['date1']['required'] = false;
    $fields['time']['required'] = false;
}

/**
 * 送信処理: クリニック宛ての通知と、入力者への自動返信
 * 自動返信には自由記述（ご相談内容）を含めず、お名前・ご用件・希望日時と定型文だけで組み立てる。
 * 第三者のアドレスを入力されても、任意の文章を送りつける踏み台にならないようにするため。
 *
 * @param array<string, mixed> $v 検証・整形済みの値
 */
$onSend = static function (array $v) use ($fields): bool {
    $option = static fn (string $key): string => (string) ($fields[$key]['options'][$v[$key]] ?? '');
    $date = static fn (string $ymd): string => $ymd !== '' ? ja_date($ymd, true) : '指定なし';
    $flag = static fn (string $ymd): string => is_closed_day($ymd) ? '　※休診日' : '';
    $hasClosedDay = is_closed_day((string) $v['date1']) || is_closed_day((string) $v['date2']);
    $clinic = (string) site('name');
    $time = $v['time'] !== '' ? $option('time') : '指定なし';

    try {
        $mailer = MailerFactory::create();
        $admin = new Mail(
            to: (string) site('mail_to'),
            subject: '【Web予約】' . $option('purpose') . '（' . $v['name'] . ' 様）',
            body: implode("\n", [
                'Webフォームから、ご予約・お問い合わせを受け付けました。',
                '',
                '■ ご用件：' . $option('purpose'),
                '■ 気になる施術：' . ($v['menu'] !== '' ? $option('menu') : '未選択'),
                '■ 第1希望日：' . $date($v['date1']) . $flag($v['date1']),
                '■ 時間帯：' . $time,
                '■ 第2希望日：' . $date($v['date2']) . $flag($v['date2']),
                '■ お名前：' . $v['name'] . '（' . $v['kana'] . '）',
                '■ メールアドレス：' . $v['email'],
                '■ 電話番号：' . $v['tel'],
                '■ ご相談内容：',
                $v['message'] !== '' ? $v['message'] : '（記入なし）',
                '',
                '――',
                '送信日時：' . date('Y年n月j日 H:i'),
                '個人情報の取り扱いへの同意：あり',
            ]),
            from: (string) site('mail_from'),
            replyTo: (string) $v['email'],
        );
        if (!$mailer->send($admin)) {
            return false;
        }

        $isReservation = $v['purpose'] !== 'other';
        $schedule = ['■ ご希望日時'];
        $schedule[] = '　第1希望：' . $date($v['date1']) . ($v['date1'] !== '' ? '　' . $time : '') . $flag($v['date1']);
        if ($v['date2'] !== '') {
            $schedule[] = '　第2希望：' . $date($v['date2']) . '　' . $time . $flag($v['date2']);
        }
        $reply = new Mail(
            to: (string) $v['email'],
            subject: '【' . $clinic . '】ご予約・お問い合わせを受け付けました',
            body: implode("\n", array_merge(
                [
                    $v['name'] . ' 様',
                    '',
                    $clinic . 'です。',
                    'ご予約・お問い合わせを受け付けました。このメールは、送信内容の控えとして自動でお送りしています。',
                    '',
                    '■ ご用件：' . $option('purpose'),
                ],
                $schedule,
                [
                    '',
                    $isReservation
                        ? 'ご予約はまだ確定していません。2営業日以内に、当院からお電話またはメールでご連絡し、日時を確定いたします。'
                        : '内容を確認のうえ、2営業日以内に当院からご連絡いたします。',
                    ...($hasClosedDay ? ['ご希望日に休診日が含まれているため、近い日程をご提案いたします。'] : []),
                    'ご相談内容は、プライバシー保護のため、このメールには記載していません。',
                    '',
                    'このメールにお心当たりのない場合は、お手数ですが破棄してください。',
                    '',
                    '――――――――',
                    $clinic . '（架空のクリニックのサンプルです）',
                    '〒' . site('address.postal_code') . ' ' . full_address(),
                    'TEL ' . site('tel'),
                    '診療時間 ' . hours_summary() . '／休診日 ' . site('closed_label'),
                ]
            )),
            from: (string) site('mail_from'),
            replyTo: (string) site('email'),
        );
        if (!$mailer->send($reply)) {
            // 受付は完了しているため、自動返信の失敗だけでは送信エラーにしない
            error_log('contact: 自動返信メールを送信できませんでした');
        }
        return true;
    } catch (InvalidArgumentException $exception) {
        error_log('contact: ' . $exception->getMessage());
        return false;
    }
};

// FormFlow はリダイレクトのため出力より前に呼ぶ。静的版はフォームを送信しない（JavaScript のデモ表示）
$form = is_static() ? null : (new FormFlow('contact', $fields, $onSend))->handle();
$step = $form['step'] ?? 'input';
$values = $form['values'] ?? [];
$errors = $form['errors'] ?? [];
$notice = $form['notice'] ?? null;
$token = $form['token'] ?? '';

// 施術ページからのリンク（contact.php?menu=ipl）では、気になる施術を選んだ状態にする
$preselect = $_GET['menu'] ?? null;
if ($step === 'input' && empty($values['menu']) && is_string($preselect) && isset($menuOptions[$preselect])) {
    $values['menu'] = $preselect;
}

$val = static fn (string $name): string => is_string($values[$name] ?? null) ? $values[$name] : '';
$invalid = static fn (string $name): string => isset($errors[$name]) ? ' aria-invalid="true"' : '';
$describedBy = static function (string $name, bool $hint = false) use ($errors): string {
    $ids = [];
    if ($hint) {
        $ids[] = 'f-' . $name . '-hint';
    }
    if (isset($errors[$name])) {
        $ids[] = 'f-' . $name . '-error';
    }
    return $ids ? ' aria-describedby="' . e(implode(' ', $ids)) . '"' : '';
};
$error = static fn (string $name): string => isset($errors[$name])
    ? '<p class="c-field__error" id="f-' . e($name) . '-error">' . e($errors[$name]) . '</p>'
    : '';
$fieldClass = static fn (string $name, string $extra = ''): string => 'c-field' . ($extra !== '' ? ' ' . $extra : '') . (isset($errors[$name]) ? ' is-invalid' : '');
$badge = static fn (bool $required): string => $required
    ? '<span class="c-field__badge c-field__badge--required">必須</span>'
    : '<span class="c-field__badge">任意</span>';

$minDate = (new DateTimeImmutable('today'))->modify('+1 day')->format('Y-m-d');
$maxDate = (new DateTimeImmutable('today'))->modify('+60 days')->format('Y-m-d');
$dateRange = is_static() ? '' : ' min="' . e($minDate) . '" max="' . e($maxDate) . '"';
$stepIndex = ['input' => 0, 'confirm' => 1, 'complete' => 2][$step] ?? 0;

$page = [
    'id' => 'contact',
    'title' => 'ご予約・お問い合わせ',
    'scripts' => ['contact'],
    'description' => '白磁スキンクリニックのご予約・お問い合わせフォームです。カウンセリングや施術のご予約を24時間受け付けています。内容を確認のうえ、2営業日以内に当院からご連絡し、日時を確定いたします。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'ご予約・お問い合わせ'],
    ],
    'jsonld' => [
        [
            '@type' => 'ContactPage',
            '@id' => absolute_url() . '#webpage',
            'url' => absolute_url(),
            'name' => 'ご予約・お問い合わせ',
            'inLanguage' => 'ja',
            'about' => ['@id' => absolute_url('') . '#business'],
        ],
        business_ld(),
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'Reservation',
      'lead' => 'Webフォームは24時間受け付けています。内容を確認のうえ、2営業日以内に当院からお電話またはメールでご連絡し、日時を確定いたします。',
  ]); ?>

  <div class="l-section l-section--flush">
    <div class="l-container p-contact">
      <div class="p-contact__main">
        <ol class="c-steps js-steps" aria-label="送信までの手順">
          <?php foreach (['入力', '確認', '完了'] as $i => $label): ?>
            <li class="c-steps__item<?= $i < $stepIndex ? ' is-done' : '' ?>"<?= $i === $stepIndex ? ' aria-current="step"' : '' ?>><span class="c-steps__num" aria-hidden="true"><?= $i + 1 ?></span><?= e($label) ?></li>
          <?php endforeach; ?>
        </ol>

        <div class="p-contact__stage js-form-stage">
        <?php if ($step === 'complete'): ?>
          <section class="c-complete" aria-labelledby="complete-title">
            <svg class="c-complete__mark" viewBox="0 0 48 48" width="48" height="48" aria-hidden="true"><circle cx="24" cy="24" r="22.5"/><path d="M15 24.5l6.2 6.2L33.5 18"/></svg>
            <h2 class="c-form__step-title js-focus-on-load" id="complete-title" tabindex="-1">送信が完了しました</h2>
            <p>ご予約・お問い合わせを受け付けました。ご入力のメールアドレスに、受付内容の控えを自動でお送りしています。</p>
            <p>ご予約はまだ確定していません。2営業日以内に、当院からお電話またはメールでご連絡し、日時を確定いたします。</p>
            <p class="c-note">控えのメールが届かない場合は、迷惑メールのフォルダをご確認いただくか、お電話（<a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a>）でお問い合わせください。</p>
            <a class="c-button c-button--ghost" href="<?= e(url('index')) ?>">ホームへ戻る</a>
          </section>

        <?php elseif ($step === 'confirm'): ?>
          <section class="c-confirm" aria-labelledby="confirm-title">
            <h2 class="c-form__step-title js-focus-on-load" id="confirm-title" tabindex="-1">入力内容の確認</h2>
            <p class="c-confirm__lead">以下の内容でよろしければ、「この内容で送信する」を押してください。</p>
            <?php if ($notice): ?>
              <div class="c-form-notice" role="alert"><p class="c-form-notice__title"><?= e($notice) ?></p></div>
            <?php endif; ?>
            <dl class="c-confirm__list">
              <?php foreach ($fields as $name => $def): ?>
                <?php
                $value = $values[$name] ?? '';
                if ($def['type'] === 'choice') {
                    $display = $value !== '' ? (string) ($def['options'][$value] ?? '') : '未選択';
                } elseif ($def['type'] === 'date') {
                    $display = $value !== '' ? ja_date((string) $value, true) : '指定なし';
                } elseif ($def['type'] === 'consent') {
                    $display = $value === '1' ? '同意する' : '';
                } else {
                    $display = (string) $value;
                }
                ?>
                <div class="c-confirm__row">
                  <dt><?= e($def['label']) ?></dt>
                  <dd><?= $def['type'] === 'textarea' ? ($display !== '' ? nl2br_e($display) : '（記入なし）') : e($display) ?></dd>
                </div>
              <?php endforeach; ?>
            </dl>
            <form class="c-confirm__actions js-confirm-form" action="<?= e(url('contact')) ?>" method="post">
              <input type="hidden" name="_token" value="<?= e($token) ?>">
              <button type="submit" class="c-button c-button--ghost" name="_action" value="back">入力内容を修正する</button>
              <button type="submit" class="c-button c-button--primary c-button--large" name="_action" value="send">この内容で送信する<?= icon('arrow') ?></button>
            </form>
          </section>

        <?php else: ?>
          <?php if ($notice): ?>
            <div class="c-form-notice js-form-notice js-focus-on-load" role="alert" tabindex="-1">
              <p class="c-form-notice__title"><?= e($notice) ?></p>
              <?php if ($errors): ?>
                <ul class="c-form-notice__list">
                  <?php foreach ($errors as $name => $message): ?>
                    <li><a href="#f-<?= e($name) ?>"><?= e($message) ?></a></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if (is_static()): ?>
            <p class="c-demo-note p-contact__demo-intro">このページはサンプルのため、送信されません。「入力内容を確認する」を押すと、確認画面と完了画面の流れを再現します（JavaScript を使用します）。</p>
          <?php endif; ?>

          <form class="c-form js-contact-form" action="<?= e(url('contact')) ?>" method="post" novalidate<?= is_static() ? ' data-demo' : '' ?> data-reception="<?= e(reception_json()) ?>" data-closed-label="<?= e(site('closed_label')) ?>">
            <?php if (!is_static()): ?>
              <input type="hidden" name="_token" value="<?= e($token) ?>">
            <?php endif; ?>
            <div class="c-form__hp" aria-hidden="true">
              <label for="f-website">ウェブサイト（入力しないでください）</label>
              <input type="text" id="f-website" name="website" value="" tabindex="-1" autocomplete="off">
            </div>

            <fieldset class="c-form__section">
              <legend class="c-form__legend">お客さまの情報</legend>

              <div class="<?= e($fieldClass('name')) ?>">
                <label class="c-field__label" for="f-name">お名前<?= $badge(true) ?></label>
                <input class="c-field__input" type="text" id="f-name" name="name" value="<?= e($val('name')) ?>" autocomplete="name" maxlength="40" required<?= $invalid('name') ?><?= $describedBy('name') ?>>
                <?= $error('name') ?>
              </div>

              <div class="<?= e($fieldClass('kana')) ?>">
                <label class="c-field__label" for="f-kana">フリガナ<?= $badge(true) ?></label>
                <input class="c-field__input" type="text" id="f-kana" name="kana" value="<?= e($val('kana')) ?>" maxlength="60" required<?= $invalid('kana') ?><?= $describedBy('kana', true) ?>>
                <p class="c-field__hint" id="f-kana-hint">全角カタカナでご入力ください。ひらがなで入力された場合は、カタカナに直して受け付けます。</p>
                <?= $error('kana') ?>
              </div>

              <div class="<?= e($fieldClass('email')) ?>">
                <label class="c-field__label" for="f-email">メールアドレス<?= $badge(true) ?></label>
                <input class="c-field__input" type="email" id="f-email" name="email" value="<?= e($val('email')) ?>" autocomplete="email" maxlength="254" spellcheck="false" required<?= $invalid('email') ?><?= $describedBy('email', true) ?>>
                <p class="c-field__hint" id="f-email-hint">受付内容の控えをお送りします。<?= e(site('mail_from')) ?> からのメールを受信できるよう設定してください。</p>
                <?= $error('email') ?>
              </div>

              <div class="<?= e($fieldClass('tel')) ?>">
                <label class="c-field__label" for="f-tel">電話番号<?= $badge(true) ?></label>
                <input class="c-field__input c-field__input--short" type="tel" id="f-tel" name="tel" value="<?= e($val('tel')) ?>" autocomplete="tel" maxlength="20" required<?= $invalid('tel') ?><?= $describedBy('tel', true) ?>>
                <p class="c-field__hint" id="f-tel-hint">日中につながりやすい番号をご入力ください（ハイフンはあってもなくてもかまいません）。</p>
                <?= $error('tel') ?>
              </div>
            </fieldset>

            <fieldset class="c-form__section">
              <legend class="c-form__legend">ご予約の内容</legend>

              <fieldset class="<?= e($fieldClass('purpose', 'c-field--group')) ?>" id="f-purpose" role="radiogroup" aria-required="true"<?= $invalid('purpose') ?><?= $describedBy('purpose') ?>>
                <legend class="c-field__label">ご用件<?= $badge(true) ?></legend>
                <div class="c-choice-list">
                  <?php foreach ($fields['purpose']['options'] as $key => $label): ?>
                    <label class="c-choice">
                      <input type="radio" name="purpose" value="<?= e($key) ?>"<?= $val('purpose') === $key ? ' checked' : '' ?> required>
                      <span class="c-choice__label"><?= e($label) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <?= $error('purpose') ?>
              </fieldset>

              <div class="<?= e($fieldClass('menu')) ?>">
                <label class="c-field__label" for="f-menu">気になる施術<?= $badge(false) ?></label>
                <div class="c-select">
                  <select class="c-field__input" id="f-menu" name="menu"<?= $invalid('menu') ?><?= $describedBy('menu') ?>>
                    <option value="">選択してください</option>
                    <?php foreach ($menuOptions as $key => $label): ?>
                      <option value="<?= e($key) ?>"<?= $val('menu') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?= $error('menu') ?>
              </div>

              <div class="c-field-row">
                <div class="<?= e($fieldClass('date1')) ?>">
                  <label class="c-field__label" for="f-date1">第1希望日<span class="js-required-badge" data-optional-for="other"><?= $badge(true) ?></span></label>
                  <input class="c-field__input c-field__input--date js-date" type="date" id="f-date1" name="date1" value="<?= e($val('date1')) ?>"<?= $dateRange ?> required<?= $invalid('date1') ?><?= $describedBy('date1', true) ?>>
                  <p class="c-field__hint" id="f-date1-hint">明日から60日先までの、休診日（<?= e(site('closed_label')) ?>）以外の日付を選べます。その他のお問い合わせの場合は空欄でもかまいません。</p>
                  <?= $error('date1') ?>
                </div>
                <div class="<?= e($fieldClass('date2')) ?>">
                  <label class="c-field__label" for="f-date2">第2希望日<?= $badge(false) ?></label>
                  <input class="c-field__input c-field__input--date js-date" type="date" id="f-date2" name="date2" value="<?= e($val('date2')) ?>"<?= $dateRange ?><?= $invalid('date2') ?><?= $describedBy('date2') ?>>
                  <?= $error('date2') ?>
                </div>
              </div>

              <fieldset class="<?= e($fieldClass('time', 'c-field--group')) ?>" id="f-time" role="radiogroup" aria-required="true"<?= $invalid('time') ?><?= $describedBy('time', true) ?>>
                <legend class="c-field__label">時間帯<span class="js-required-badge" data-optional-for="other"><?= $badge(true) ?></span></legend>
                <div class="c-choice-list c-choice-list--compact">
                  <?php foreach ($fields['time']['options'] as $key => $label): ?>
                    <label class="c-choice">
                      <input type="radio" name="time" value="<?= e($key) ?>"<?= $val('time') === $key ? ' checked' : '' ?> required>
                      <span class="c-choice__label"><?= e($label) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <p class="c-field__hint" id="f-time-hint">第1・第2希望日に共通の時間帯です。日曜日は16:30が最終受付です。木曜・祝日・年末年始は休診です。</p>
                <?= $error('time') ?>
              </fieldset>
            </fieldset>

            <div class="c-form__section">
              <div class="<?= e($fieldClass('message')) ?>">
                <label class="c-field__label" for="f-message">ご相談内容<?= $badge(false) ?></label>
                <textarea class="c-field__input c-field__input--textarea js-counter" id="f-message" name="message" rows="6" maxlength="1000"<?= $invalid('message') ?><?= $describedBy('message', true) ?>><?= e($val('message')) ?></textarea>
                <p class="c-field__hint" id="f-message-hint">気になっている症状や、これまでに受けた治療などをご記入ください（1000文字以内）。</p>
                <?= $error('message') ?>
              </div>

              <div class="<?= e($fieldClass('consent', 'c-field--consent')) ?>">
                <p class="c-consent__text" id="f-consent-hint">ご入力いただいた内容は、ご予約の受付とご連絡のために利用します。ご相談内容など健康に関する情報は、ご予約とカウンセリングのためにのみ利用します。詳しくは<a href="<?= e(url('privacy')) ?>">プライバシーポリシー</a>をご確認ください。</p>
                <label class="c-checkbox">
                  <input type="checkbox" id="f-consent" name="consent" value="1"<?= $val('consent') === '1' ? ' checked' : '' ?> required<?= $invalid('consent') ?><?= $describedBy('consent', true) ?>>
                  <span class="c-checkbox__label">プライバシーポリシーに同意する</span><?= $badge(true) ?>
                </label>
                <?= $error('consent') ?>
              </div>
            </div>

            <div class="c-form__actions">
              <button type="submit" class="c-button c-button--primary c-button--large js-submit" name="_action" value="confirm"<?= is_static() ? ' disabled' : '' ?>>入力内容を確認する<?= icon('arrow') ?></button>
              <?php if (is_static()): ?>
                <p class="c-form__demo-note">サンプルのため送信されません。</p>
              <?php endif; ?>
            </div>
          </form>

          <?php if (is_static()): ?>
            <template id="demo-confirm">
              <section class="c-confirm" aria-labelledby="demo-confirm-title">
                <h2 class="c-form__step-title" id="demo-confirm-title" tabindex="-1">入力内容の確認</h2>
                <p class="c-demo-note">サンプルのため送信されません。実際のサイトでは、この画面で内容を確かめてから送信します。</p>
                <p class="c-confirm__lead">以下の内容でよろしければ、「この内容で送信する」を押してください。</p>
                <dl class="c-confirm__list js-demo-list"></dl>
                <div class="c-confirm__actions">
                  <button type="button" class="c-button c-button--ghost js-demo-back">入力内容を修正する</button>
                  <button type="button" class="c-button c-button--primary c-button--large js-demo-send">この内容で送信する</button>
                </div>
              </section>
            </template>
            <template id="demo-complete">
              <section class="c-complete" aria-labelledby="demo-complete-title">
                <svg class="c-complete__mark" viewBox="0 0 48 48" width="48" height="48" aria-hidden="true"><circle cx="24" cy="24" r="22.5"/><path d="M15 24.5l6.2 6.2L33.5 18"/></svg>
                <h2 class="c-form__step-title" id="demo-complete-title" tabindex="-1">送信が完了しました（デモ）</h2>
                <p class="c-demo-note">サンプルのため送信されません。入力された内容は、このページの外には送られていません。</p>
                <p>実際のサイトでは、この画面を表示するのと同時に、クリニックへの通知メールと、ご入力のメールアドレスへの控えのメール（自動返信）が送られます。控えのメールには、ご相談内容などの自由記述は記載しません。</p>
                <button type="button" class="c-button c-button--ghost js-demo-restart">入力画面に戻る</button>
              </section>
            </template>
          <?php endif; ?>
        <?php endif; ?>
        </div>
      </div>

      <aside class="p-contact__side" aria-labelledby="contact-side-title">
        <h2 class="p-contact__side-title" id="contact-side-title">お電話でのご予約</h2>
        <a class="p-contact__tel" href="<?= e(tel_href()) ?>"><?= icon('tel') ?><span><?= e(site('tel')) ?></span></a>
        <p class="p-contact__hours">受付 <?= e(hours_summary()) ?><br>休診日 <?= e(site('closed_label')) ?></p>
        <p class="p-contact__note">当日のご予約は、お電話でお問い合わせください。</p>
        <h3 class="p-contact__side-heading">ご予約の前に</h3>
        <ul class="c-dash-list p-contact__list">
          <li>18歳未満の方は、親権者の同意書が必要です。初回は親権者の方の同伴をお願いしています。</li>
          <li>妊娠中・授乳中の方は、施術をお受けいただけない場合があります。</li>
          <li>ご予約の変更・キャンセルは、前日の19時までにご連絡ください。</li>
        </ul>
        <a class="c-text-link" href="<?= e(url('faq')) ?>">よくあるご質問<?= icon('arrow') ?></a>
      </aside>
    </div>
  </div>
</main>
<?php partial('footer', compact('page')); ?>
