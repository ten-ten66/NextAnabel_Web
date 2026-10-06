<?php
require __DIR__ . '/_init.php';

use Core\Form\FormFlow;
use Core\Form\Mail;
use Core\Form\MailerFactory;

$menuOptions = array_column(sc_treatments(), 'name', 'slug');
$menuOptions['undecided'] = 'その他・まだ決めていない';
$timeOptions = [
    'morning' => '午前（10:00〜12:00）',
    'afternoon' => '午後（12:00〜15:00）',
    'evening' => '夕方（15:00〜18:00）',
    'any' => '指定なし',
];

$fields = [
    'name' => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'max' => 50],
    'kana' => ['label' => 'フリガナ', 'type' => 'kana', 'required' => true, 'max' => 50],
    'email' => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true, 'max' => 254],
    'tel' => ['label' => '電話番号', 'type' => 'tel', 'required' => true, 'max' => 20],
    'menu' => ['label' => 'ご希望の施術', 'type' => 'choices', 'required' => true, 'options' => $menuOptions],
    'date1' => ['label' => '第1希望日', 'type' => 'date', 'required' => true, 'min_days' => 1, 'max_days' => 60, 'closed_weekdays' => [0], 'closed_dates' => (array) site('closed_dates', []), 'closed_label' => (string) site('closed_label')],
    'time' => ['label' => 'ご希望の時間帯', 'type' => 'choice', 'required' => true, 'options' => $timeOptions],
    'date2' => ['label' => '第2希望日', 'type' => 'date', 'required' => false, 'min_days' => 1, 'max_days' => 60, 'closed_weekdays' => [0], 'closed_dates' => (array) site('closed_dates', []), 'closed_label' => (string) site('closed_label')],
    'message' => ['label' => 'ご相談内容', 'type' => 'textarea', 'required' => false, 'max' => 1000],
    'consent' => ['label' => '個人情報の取り扱い', 'type' => 'consent', 'required' => true, 'message' => 'プライバシーポリシーへの同意が必要です。'],
];

/**
 * 送信処理。管理者宛てとお客様への自動返信の2通を送る。
 * 自動返信には、お名前・ご相談内容などの自由入力を含めない
 * （第三者のアドレスを入力して迷惑メールの送信に悪用されるのを防ぐため）。
 */
$send = static function (array $v) use ($menuOptions, $timeOptions): bool {
    $menu = implode('、', array_map(static fn (string $key): string => $menuOptions[$key], $v['menu']));
    $time = $timeOptions[$v['time']] ?? '';
    $date1 = sc_date_label($v['date1']);
    $date2 = $v['date2'] !== '' ? sc_date_label($v['date2']) : '指定なし';
    $siteName = (string) site('name');
    $tel = (string) site('tel');

    $adminBody = implode("\n", [
        'Webサイトのフォームから、カウンセリング予約のお申し込みがありました。',
        '2営業日以内に、お電話またはメールで日時を確定してください。',
        '',
        '受付日時：' . date('Y年n月j日 H:i'),
        'お名前：' . $v['name'],
        'フリガナ：' . $v['kana'],
        'メールアドレス：' . $v['email'],
        '電話番号：' . $v['tel'],
        'ご希望の施術：' . $menu,
        '第1希望日：' . $date1,
        'ご希望の時間帯：' . $time,
        '第2希望日：' . $date2,
        '',
        '【ご相談内容】',
        $v['message'] !== '' ? $v['message'] : '（記入なし）',
    ]);
    $replyBody = implode("\n", [
        "このメールは、{$siteName}のWebサイトからカウンセリング予約をお申し込みいただいた方へ、自動でお送りしています。",
        '',
        'お申し込みを受け付けました。内容を確認のうえ、2営業日以内にお電話またはメールで、カウンセリングの日時をご連絡します。日時が確定するまで、ご予約は完了していませんのでご注意ください。',
        '',
        '■ お申し込み内容',
        'ご希望の施術：' . $menu,
        '第1希望日：' . $date1,
        'ご希望の時間帯：' . $time,
        '第2希望日：' . $date2,
        '',
        '■ カウンセリングについて',
        '・カウンセリング料：' . tax_in((int) site('counseling_fee')),
        '・所要時間：約' . site('counseling_minutes') . '分',
        '・カウンセリング当日に手術を行うことはありません。',
        '',
        'お心当たりのない方は、お手数ですがこのメールを破棄してください。',
        '',
        '――――――――――――',
        $siteName,
        site('address.region') . site('address.locality') . site('address.street'),
        "TEL {$tel}（" . site('hours_label') . '／休診 ' . site('closed_label') . '）',
    ]);

    try {
        $mailer = MailerFactory::create();
        $admin = new Mail((string) site('mail_to'), '【カウンセリング予約】Webフォームからのお申し込み', $adminBody, (string) site('mail_from'), $v['email']);
        $reply = new Mail($v['email'], "【{$siteName}】カウンセリング予約のお申し込みを受け付けました", $replyBody, (string) site('mail_from'));
        return $mailer->send($admin) && $mailer->send($reply);
    } catch (InvalidArgumentException) {
        return false;
    }
};

// サーバー版のみ送信処理を行う（リダイレクトのため出力より前に呼ぶ）。静的版は入力画面だけを書き出し、JavaScript でデモ表示する
$form = is_static()
    ? ['step' => 'input', 'values' => [], 'errors' => [], 'notice' => null, 'token' => '', 'fields' => $fields]
    : (new FormFlow('counseling', $fields, $send))->handle();

$step = $form['step'];
$values = $form['values'];
$errors = $form['errors'];
$val = static fn (string $name): string => is_string($values[$name] ?? null) ? $values[$name] : '';
$checked = static fn (string $name, string $option): bool => in_array($option, (array) ($values[$name] ?? []), true) || ($values[$name] ?? null) === $option;
// aria-describedby と aria-invalid（ヒントがある項目はヒントも関連付ける）
// fieldset（グループ）には aria-invalid を付けず、中の入力欄に付ける
$aria = static function (string $name, bool $hint = false, bool $group = false) use ($errors): string {
    $ids = ($hint ? "f-{$name}-hint " : '') . "f-{$name}-error";
    return ' aria-describedby="' . e($ids) . '"' . (isset($errors[$name]) && !$group ? ' aria-invalid="true"' : '');
};
$error = static function (string $name) use ($errors): string {
    $message = $errors[$name] ?? '';
    return '<p class="c-field__error" id="f-' . e($name) . '-error" data-error-for="' . e($name) . '"' . ($message === '' ? ' hidden' : '') . '>' . e($message) . '</p>';
};
$minDate = (new DateTimeImmutable('today'))->modify('+1 day')->format('Y-m-d');
$maxDate = (new DateTimeImmutable('today'))->modify('+60 days')->format('Y-m-d');
$dateRange = is_static() ? '' : ' min="' . e($minDate) . '" max="' . e($maxDate) . '"';
$stepIndex = ['input' => 0, 'confirm' => 1, 'complete' => 2][$step] ?? 0;

$page = [
    'id' => 'counseling',
    'title' => 'カウンセリング予約',
    'description' => 'オルヴァン美容外科のカウンセリング予約フォームです。ご希望の施術と日時を2つまでお選びください。医師が施術の適応・リスクと副作用・費用の総額をご説明します。カウンセリング当日に手術は行いません。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'カウンセリング予約'],
    ],
];

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'kicker' => 'Counseling',
      'title' => 'カウンセリング予約',
      'lead' => 'ご希望の日時を2つまでお選びください。内容を確認のうえ、2営業日以内にお電話またはメールでご連絡し、ご予約を確定します。',
  ]); ?>

  <div class="l-container p-counseling l-section">
    <aside class="p-counseling__aside" aria-labelledby="about-title">
      <h2 class="p-counseling__aside-title" id="about-title">カウンセリングについて</h2>
      <dl class="p-counseling__facts">
        <div><dt>カウンセリング料</dt><dd><?= e(tax_in((int) site('counseling_fee'))) ?></dd></div>
        <div><dt>所要時間</dt><dd>約<?= e(site('counseling_minutes')) ?>分（診察を含みます）</dd></div>
        <div><dt>担当</dt><dd><?= e(sc_reviewer_label()) ?></dd></div>
      </dl>
      <ul class="c-dash-list p-counseling__notes">
        <li>カウンセリング当日に手術を行うことはありません。費用やリスクをお持ち帰りのうえ、ご検討ください。</li>
        <li>未成年の方は、保護者の同意書が必要です。</li>
        <li>当日のご予約・お急ぎの方は、お電話でお問い合わせください。</li>
      </ul>
      <a class="p-counseling__tel" href="<?= e(sc_tel_href()) ?>"><span>TEL</span><?= e(site('tel')) ?></a>
      <p class="p-counseling__hours"><?= e(site('hours_label')) ?>／休診 <?= e(site('closed_label')) ?></p>
    </aside>

    <div class="p-counseling__main">
      <ol class="c-form-steps" aria-label="お申し込みの手順" data-form-steps>
        <?php foreach (['入力', '確認', '完了'] as $i => $label): ?>
          <li class="c-form-steps__item<?= $i === $stepIndex ? ' is-current' : '' ?><?= $i < $stepIndex ? ' is-done' : '' ?>"<?= $i === $stepIndex ? ' aria-current="step"' : '' ?>>
            <span class="c-form-steps__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span><?= e($label) ?>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if ($step === 'complete'): ?>
        <section class="p-form__panel p-form__complete" aria-labelledby="complete-title" tabindex="-1" data-focus-on-load>
          <h2 class="p-form__title" id="complete-title">お申し込みを受け付けました</h2>
          <p>カウンセリング予約のお申し込みをいただき、ありがとうございます。内容を確認のうえ、2営業日以内にお電話またはメールで、カウンセリングの日時をご連絡します。日時が確定するまで、ご予約は完了していませんのでご注意ください。</p>
          <p>ご入力いただいたメールアドレスに、受付確認のメールを自動でお送りしています。届かない場合は、お手数ですがお電話でお問い合わせください。</p>
          <a class="c-link" href="<?= e(url('index')) ?>">トップページへ戻る</a>
        </section>

      <?php elseif ($step === 'confirm'): ?>
        <section class="p-form__panel p-form__confirm" aria-labelledby="confirm-title" tabindex="-1" data-focus-on-load>
          <h2 class="p-form__title" id="confirm-title">入力内容の確認</h2>
          <p>以下の内容でお申し込みます。よろしければ「この内容で送信する」を押してください。</p>
          <?php if ($form['notice'] !== null): ?><p class="c-alert" role="alert"><?= e($form['notice']) ?></p><?php endif; ?>
          <dl class="c-confirm">
            <div class="c-confirm__row"><dt>お名前</dt><dd><?= e($val('name')) ?></dd></div>
            <div class="c-confirm__row"><dt>フリガナ</dt><dd><?= e($val('kana')) ?></dd></div>
            <div class="c-confirm__row"><dt>メールアドレス</dt><dd><?= e($val('email')) ?></dd></div>
            <div class="c-confirm__row"><dt>電話番号</dt><dd><?= e($val('tel')) ?></dd></div>
            <div class="c-confirm__row"><dt>ご希望の施術</dt><dd><?= e(implode('、', array_map(static fn (string $k): string => $menuOptions[$k], (array) ($values['menu'] ?? [])))) ?></dd></div>
            <div class="c-confirm__row"><dt>第1希望日</dt><dd><?= e(sc_date_label($val('date1'))) ?></dd></div>
            <div class="c-confirm__row"><dt>ご希望の時間帯</dt><dd><?= e($timeOptions[$val('time')] ?? '') ?></dd></div>
            <div class="c-confirm__row"><dt>第2希望日</dt><dd><?= e($val('date2') !== '' ? sc_date_label($val('date2')) : '指定なし') ?></dd></div>
            <div class="c-confirm__row"><dt>ご相談内容</dt><dd><?= $val('message') !== '' ? nl2br_e($val('message')) : '記入なし' ?></dd></div>
            <div class="c-confirm__row"><dt>個人情報の取り扱い</dt><dd>同意する</dd></div>
          </dl>
          <form class="p-form__actions" method="post" action="<?= e(url('counseling')) ?>" data-submit-once>
            <input type="hidden" name="_token" value="<?= e($form['token']) ?>">
            <button class="c-button c-button--ghost" type="submit" name="_action" value="back">入力画面に戻る</button>
            <button class="c-button" type="submit" name="_action" value="send">この内容で送信する<span class="c-button__arrow" aria-hidden="true"></span></button>
          </form>
        </section>

      <?php else: ?>
        <div class="p-form" data-form-root>
          <?php if ($errors): ?>
            <div class="c-error-summary" tabindex="-1" aria-labelledby="error-summary-title" data-error-summary data-focus-on-load>
              <h2 class="c-error-summary__title" id="error-summary-title">入力内容をご確認ください</h2>
              <p><?= e($form['notice'] ?? '') ?></p>
              <ul class="c-error-summary__list">
                <?php foreach ($errors as $name => $message): ?>
                  <li><a href="#f-<?= e($name === 'menu' || $name === 'time' ? $name . '-0' : $name) ?>"><?= e($message) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php elseif ($form['notice'] !== null): ?>
            <p class="c-alert" role="alert"><?= e($form['notice']) ?></p>
          <?php else: ?>
            <div class="c-error-summary" tabindex="-1" aria-labelledby="error-summary-title" data-error-summary hidden>
              <h2 class="c-error-summary__title" id="error-summary-title">入力内容をご確認ください</h2>
              <ul class="c-error-summary__list" data-error-list></ul>
            </div>
          <?php endif; ?>

          <form class="p-form__form" method="post" action="<?= e(url('counseling')) ?>" novalidate data-counseling-form data-closed-dates="<?= e(implode(' ', (array) site('closed_dates', []))) ?>"<?= is_static() ? ' data-demo-form' : ' data-submit-once' ?>>
            <h2 class="p-form__title">ご予約内容の入力</h2>
            <p class="p-form__required-note"><span class="c-field__req">必須</span>の項目は必ずご入力ください。</p>
            <?php if (!is_static()): ?><input type="hidden" name="_token" value="<?= e($form['token']) ?>"><?php endif; ?>

            <div class="p-form__grid">
              <div class="c-field" data-field="name">
                <label class="c-field__label" for="f-name">お名前<span class="c-field__req">必須</span></label>
                <input class="c-field__input" id="f-name" name="name" type="text" autocomplete="name" maxlength="50" required value="<?= e($val('name')) ?>"<?= $aria('name', true) ?>>
                <p class="c-field__hint" id="f-name-hint">例：銀座 花子</p>
                <?= $error('name') ?>
              </div>
              <div class="c-field" data-field="kana">
                <label class="c-field__label" for="f-kana">フリガナ<span class="c-field__req">必須</span></label>
                <input class="c-field__input" id="f-kana" name="kana" type="text" maxlength="50" required value="<?= e($val('kana')) ?>"<?= $aria('kana', true) ?>>
                <p class="c-field__hint" id="f-kana-hint">例：ギンザ ハナコ（全角カタカナ）</p>
                <?= $error('kana') ?>
              </div>
              <div class="c-field" data-field="email">
                <label class="c-field__label" for="f-email">メールアドレス<span class="c-field__req">必須</span></label>
                <input class="c-field__input" id="f-email" name="email" type="email" autocomplete="email" inputmode="email" maxlength="254" required value="<?= e($val('email')) ?>"<?= $aria('email', true) ?>>
                <p class="c-field__hint" id="f-email-hint">受付確認のメールをお送りします。</p>
                <?= $error('email') ?>
              </div>
              <div class="c-field" data-field="tel">
                <label class="c-field__label" for="f-tel">電話番号<span class="c-field__req">必須</span></label>
                <input class="c-field__input" id="f-tel" name="tel" type="tel" autocomplete="tel" inputmode="tel" maxlength="20" required value="<?= e($val('tel')) ?>"<?= $aria('tel', true) ?>>
                <p class="c-field__hint" id="f-tel-hint">日中にご連絡のつく番号（例：090-0000-0000）</p>
                <?= $error('tel') ?>
              </div>
            </div>

            <fieldset class="c-field c-field--group" data-field="menu"<?= $aria('menu', true, true) ?>>
              <legend class="c-field__label">ご希望の施術<span class="c-field__req">必須</span></legend>
              <p class="c-field__hint" id="f-menu-hint">複数選択できます。</p>
              <div class="c-choices">
                <?php $i = 0; foreach ($menuOptions as $key => $label): ?>
                  <label class="c-choice">
                    <input class="c-choice__input" id="f-menu-<?= $i ?>" type="checkbox" name="menu[]" value="<?= e($key) ?>"<?= $checked('menu', $key) ? ' checked' : '' ?><?= isset($errors['menu']) ? ' aria-invalid="true"' : '' ?>>
                    <span class="c-choice__box" aria-hidden="true"></span>
                    <span class="c-choice__text"><?= e($label) ?></span>
                  </label>
                <?php $i++; endforeach; ?>
              </div>
              <?= $error('menu') ?>
            </fieldset>

            <div class="p-form__grid">
              <div class="c-field" data-field="date1">
                <label class="c-field__label" for="f-date1">第1希望日<span class="c-field__req">必須</span></label>
                <input class="c-field__input c-field__input--date" id="f-date1" name="date1" type="date" required value="<?= e($val('date1')) ?>" data-min-days="1" data-max-days="60"<?= $dateRange ?><?= $aria('date1', true) ?>>
                <p class="c-field__hint" id="f-date1-hint">明日から60日先までの日付をお選びください（日曜・祝日は休診）。</p>
                <?= $error('date1') ?>
              </div>
              <div class="c-field" data-field="date2">
                <label class="c-field__label" for="f-date2">第2希望日<span class="c-field__opt">任意</span></label>
                <input class="c-field__input c-field__input--date" id="f-date2" name="date2" type="date" value="<?= e($val('date2')) ?>" data-min-days="1" data-max-days="60"<?= $dateRange ?><?= $aria('date2') ?>>
                <?= $error('date2') ?>
              </div>
            </div>

            <fieldset class="c-field c-field--group" data-field="time"<?= $aria('time', false, true) ?>>
              <legend class="c-field__label">ご希望の時間帯<span class="c-field__req">必須</span></legend>
              <div class="c-choices c-choices--inline">
                <?php $i = 0; foreach ($timeOptions as $key => $label): ?>
                  <label class="c-choice">
                    <input class="c-choice__input" id="f-time-<?= $i ?>" type="radio" name="time" value="<?= e($key) ?>"<?= $checked('time', $key) ? ' checked' : '' ?><?= isset($errors['time']) ? ' aria-invalid="true"' : '' ?>>
                    <span class="c-choice__box c-choice__box--radio" aria-hidden="true"></span>
                    <span class="c-choice__text"><?= e($label) ?></span>
                  </label>
                <?php $i++; endforeach; ?>
              </div>
              <?= $error('time') ?>
            </fieldset>

            <div class="c-field" data-field="message">
              <label class="c-field__label" for="f-message">ご相談内容<span class="c-field__opt">任意</span></label>
              <textarea class="c-field__input c-field__input--textarea" id="f-message" name="message" rows="6" maxlength="1000"<?= $aria('message', true) ?>><?= e($val('message')) ?></textarea>
              <p class="c-field__hint" id="f-message-hint">気になっている部位や、ご希望の仕上がり、ご不安な点などをご記入ください（1000文字以内）。</p>
              <?= $error('message') ?>
            </div>

            <div class="c-field c-field--consent" data-field="consent">
              <p class="c-field__consent-text" id="f-consent-hint">ご入力いただいた個人情報は、カウンセリングのご予約とご連絡のためにのみ使用します。詳しくは<a href="<?= e(url('privacy')) ?>">プライバシーポリシー</a>をご確認ください。</p>
              <label class="c-choice c-choice--consent">
                <input class="c-choice__input" id="f-consent" type="checkbox" name="consent" value="1" required<?= $val('consent') === '1' ? ' checked' : '' ?><?= $aria('consent', true) ?>>
                <span class="c-choice__box" aria-hidden="true"></span>
                <span class="c-choice__text">プライバシーポリシーに同意する<span class="c-field__req">必須</span></span>
              </label>
              <?= $error('consent') ?>
            </div>

            <div class="u-honeypot" aria-hidden="true">
              <label for="f-website">このフィールドは入力しないでください</label>
              <input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="p-form__actions">
              <button class="c-button c-button--large" type="submit" name="_action" value="confirm">入力内容を確認する<span class="c-button__arrow" aria-hidden="true"></span></button>
            </div>
            <?php if (is_static()): ?>
              <p class="p-form__demo-note" data-lint-ignore>このページはサンプルです。確認画面・完了画面は表示されますが、入力内容は送信されません。</p>
              <noscript><p class="p-form__demo-note">確認画面・完了画面のデモは、JavaScript を有効にするとご覧いただけます。</p></noscript>
            <?php endif; ?>
          </form>

          <?php if (is_static()): ?>
            <section class="p-form__panel p-form__confirm" aria-labelledby="demo-confirm-title" tabindex="-1" hidden data-demo-confirm>
              <h2 class="p-form__title" id="demo-confirm-title">入力内容の確認</h2>
              <p>以下の内容でお申し込みます。よろしければ「この内容で送信する」を押してください。</p>
              <dl class="c-confirm">
                <div class="c-confirm__row"><dt>お名前</dt><dd data-confirm="name"></dd></div>
                <div class="c-confirm__row"><dt>フリガナ</dt><dd data-confirm="kana"></dd></div>
                <div class="c-confirm__row"><dt>メールアドレス</dt><dd data-confirm="email"></dd></div>
                <div class="c-confirm__row"><dt>電話番号</dt><dd data-confirm="tel"></dd></div>
                <div class="c-confirm__row"><dt>ご希望の施術</dt><dd data-confirm="menu"></dd></div>
                <div class="c-confirm__row"><dt>第1希望日</dt><dd data-confirm="date1"></dd></div>
                <div class="c-confirm__row"><dt>ご希望の時間帯</dt><dd data-confirm="time"></dd></div>
                <div class="c-confirm__row"><dt>第2希望日</dt><dd data-confirm="date2"></dd></div>
                <div class="c-confirm__row"><dt>ご相談内容</dt><dd class="c-confirm__pre" data-confirm="message"></dd></div>
                <div class="c-confirm__row"><dt>個人情報の取り扱い</dt><dd>同意する</dd></div>
              </dl>
              <div class="p-form__actions">
                <button class="c-button c-button--ghost" type="button" data-demo-back>入力画面に戻る</button>
                <button class="c-button" type="button" data-demo-send>この内容で送信する<span class="c-button__arrow" aria-hidden="true"></span></button>
              </div>
              <p class="p-form__demo-note" data-lint-ignore>サンプルのため、送信ボタンを押しても入力内容は送信されません。</p>
            </section>
            <section class="p-form__panel p-form__complete" aria-labelledby="demo-complete-title" tabindex="-1" hidden data-demo-complete>
              <h2 class="p-form__title" id="demo-complete-title">お申し込みを受け付けました</h2>
              <p class="p-form__demo-badge" data-lint-ignore>サンプルのため送信されません</p>
              <p>実際のサイトでは、ここで受付完了をお知らせし、ご入力いただいたメールアドレスに受付確認のメールを自動でお送りします。内容を確認のうえ、2営業日以内にお電話またはメールでご連絡し、ご予約を確定します。</p>
              <a class="c-link" href="<?= e(url('index')) ?>">トップページへ戻る</a>
            </section>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php partial('footer'); ?>
