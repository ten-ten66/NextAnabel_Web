<?php
/**
 * カウンセリング予約フォーム（入力 → 確認 → 完了 をページ内で切り替える）
 *
 * サーバー版: JavaScript が api/reserve.php に JSON で送信する（CSRF トークンは head の meta）。
 * 静的版   : 送信せず、確認 → 完了をその場で表示する（サンプルのため送信されない旨を明示）。
 * 項目と選択肢は lib/reservation.php の定義（API の検証と同じもの）から出力する。
 *
 * @var array<string, mixed> $content
 */
$fields = lp_reservation_fields();
$slots = lp_time_slots();
$closed = lp_closed_weekdays();
$closedNames = implode('・', array_map(static fn ($d) => LP_WEEKDAYS[$d], $closed));
$dateMin = (new DateTimeImmutable('today'))->modify('+' . $fields['date1']['min_days'] . ' days')->format('Y-m-d');
$dateMax = (new DateTimeImmutable('today'))->modify('+' . $fields['date1']['max_days'] . ' days')->format('Y-m-d');
$static = is_static();
$required = '<span class="c-field__badge">必須</span>';
$optional = '<span class="c-field__badge c-field__badge--optional">任意</span>';
?>
<section class="p-reserve l-section" id="reserve" aria-labelledby="reserve-title">
  <div class="p-reserve__bg" aria-hidden="true">
    <img src="<?= e(asset('img/section-bg.webp')) ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
  </div>
  <div class="l-container">
    <div class="c-section-head c-section-head--center">
      <p class="c-section-head__label"><span class="c-section-head__num">08</span>ご予約</p>
      <h2 class="c-section-head__title js-reserve-heading" id="reserve-title" tabindex="-1">カウンセリングを予約する（無料）</h2>
      <p class="c-section-head__lead">必須項目は5つです。ご予約は、クリニックから日時を確定するご連絡をした時点で確定します。</p>
    </div>

    <div class="p-reserve__card">
      <ol class="c-steps js-steps" aria-label="予約の手順">
        <li class="c-steps__item is-current" aria-current="step"><span class="c-steps__num" aria-hidden="true">1</span>入力</li>
        <li class="c-steps__item"><span class="c-steps__num" aria-hidden="true">2</span>確認</li>
        <li class="c-steps__item"><span class="c-steps__num" aria-hidden="true">3</span>完了</li>
      </ol>

<?php if ($static): ?>
      <p class="p-reserve__demo"><?= lp_icon('alert') ?><span>サンプルのため送信されません。入力 → 確認 → 完了の流れをお試しいただけます。</span></p>
<?php endif; ?>

      <p class="p-reserve__prefill js-prefill-note" hidden><?= lp_icon('check') ?><span class="js-prefill-text"></span></p>

      <form class="p-reserve__form js-reserve-form" id="reserve-form"<?= $static ? '' : ' action="api/reserve.php" method="post" data-endpoint="api/reserve.php"' ?> data-closed-days="<?= e(implode(',', $closed)) ?>" data-min-days="<?= e((string) $fields['date1']['min_days']) ?>" data-max-days="<?= e((string) $fields['date1']['max_days']) ?>">
        <div class="c-error-summary js-error-summary" tabindex="-1" hidden>
          <p class="c-error-summary__title"><?= lp_icon('alert') ?><span class="js-error-summary-title">入力内容をご確認ください</span></p>
          <ul class="c-error-summary__list js-error-summary-list"></ul>
        </div>

        <div class="c-field" data-field="name">
          <label class="c-field__label" for="f-name">お名前<?= $required ?></label>
          <input class="c-field__input js-field" id="f-name" name="name" type="text" autocomplete="name" maxlength="<?= e((string) $fields['name']['max']) ?>" placeholder="例）白磁 花子" required data-label="お名前" data-type="text">
          <p class="c-field__error" id="f-name-error" hidden></p>
        </div>

        <div class="c-field" data-field="tel">
          <label class="c-field__label" for="f-tel">電話番号<?= $required ?></label>
          <input class="c-field__input js-field" id="f-tel" name="tel" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="例）090-0000-0000" required aria-describedby="f-tel-hint" data-label="電話番号" data-type="tel">
          <p class="c-field__hint" id="f-tel-hint">日時を確定するご連絡に使います。ハイフンはなくてもかまいません。</p>
          <p class="c-field__error" id="f-tel-error" hidden></p>
        </div>

        <div class="c-field" data-field="email">
          <label class="c-field__label" for="f-email">メールアドレス<?= $required ?></label>
          <input class="c-field__input js-field" id="f-email" name="email" type="email" autocomplete="email" maxlength="<?= e((string) $fields['email']['max']) ?>" placeholder="例）hanako@example.com" required aria-describedby="f-email-hint" data-label="メールアドレス" data-type="email">
          <p class="c-field__hint" id="f-email-hint">受付の確認メールをお送りします。</p>
          <p class="c-field__error" id="f-email-error" hidden></p>
        </div>

        <fieldset class="c-field c-field--group" data-field="parts" aria-describedby="f-parts-hint">
          <legend class="c-field__label">希望部位<?= $optional ?></legend>
          <p class="c-field__hint" id="f-parts-hint">決まっていなければ、選ばなくてもかまいません。シミュレーターで選んだ部位は、ここに入力されます。</p>
          <div class="c-field__chips">
<?php foreach ($fields['parts']['options'] as $id => $label): ?>
<?php [$name] = lp_split_label($label); ?>
            <label class="c-chipcheck">
              <input class="c-chipcheck__input js-field js-form-part" id="f-parts-<?= e($id) ?>" type="checkbox" name="parts[]" value="<?= e($id) ?>" data-label="希望部位" data-type="choices" data-full-label="<?= e($label) ?>">
              <span class="c-chipcheck__body"><?= lp_icon('check') ?><?= e($name) ?></span>
            </label>
<?php endforeach; ?>
          </div>
        </fieldset>

        <div class="c-field__pair">
          <div class="c-field" data-field="date1">
            <label class="c-field__label" for="f-date1">第1希望日<?= $required ?></label>
            <input class="c-field__input c-field__input--date js-field" id="f-date1" name="date1" type="date"<?= $static ? '' : ' min="' . e($dateMin) . '" max="' . e($dateMax) . '"' ?> required aria-describedby="f-date1-hint" data-label="第1希望日" data-type="date">
            <p class="c-field__hint" id="f-date1-hint"><?= e($closedNames) ?>曜・祝日は休診です。明日から<?= e((string) $fields['date1']['max_days']) ?>日先まで選べます。</p>
            <p class="c-field__error" id="f-date1-error" hidden></p>
          </div>

          <fieldset class="c-field c-field--group" data-field="time1">
            <legend class="c-field__label">ご希望の時間帯<?= $required ?></legend>
            <div class="c-field__segments">
<?php foreach ($slots as $id => $label): ?>
              <label class="c-segment">
                <input class="c-segment__input js-field" id="f-time1-<?= e($id) ?>" type="radio" name="time1" value="<?= e($id) ?>" required data-label="ご希望の時間帯" data-type="choice">
                <span class="c-segment__body"><?= e($label) ?></span>
              </label>
<?php endforeach; ?>
            </div>
            <p class="c-field__error" id="f-time1-error" hidden></p>
          </fieldset>
        </div>

        <div class="c-field" data-field="message">
          <label class="c-field__label" for="f-message">ご質問など<?= $optional ?></label>
          <textarea class="c-field__input c-field__input--textarea js-field" id="f-message" name="message" rows="4" maxlength="<?= e((string) LP_MESSAGE_MAX) ?>" aria-describedby="f-message-hint" data-label="ご質問など" data-type="textarea"></textarea>
          <p class="c-field__hint" id="f-message-hint"><?= e((string) LP_MESSAGE_MAX) ?>文字以内。気になる部位や、肌のことなどをご自由にお書きください。</p>
          <p class="c-field__error" id="f-message-error" hidden></p>
        </div>

        <div class="c-field c-field--consent" data-field="consent">
          <details class="p-reserve__privacy">
            <summary>個人情報の取り扱いについて（要約）</summary>
            <dl class="p-reserve__privacy-list">
<?php foreach ($content['privacy'] as $item): ?>
              <div><dt><?= e($item['title']) ?></dt><dd><?= e($item['body']) ?></dd></div>
<?php endforeach; ?>
            </dl>
            <p class="p-reserve__privacy-more">全文は<a href="<?= e(lp_corporate_url()) ?>" target="_blank" rel="noopener"><?= e(site('name')) ?>の公式サイト<span class="u-visually-hidden">（新しいタブで開きます）</span></a>でご案内しています。</p>
          </details>
          <label class="c-check">
            <input class="c-check__input js-field" id="f-consent" type="checkbox" name="consent" value="1" required data-label="個人情報の取り扱い" data-type="consent">
            <span class="c-check__box" aria-hidden="true"><?= lp_icon('check') ?></span>
            <span class="c-check__text">個人情報の取り扱いに同意する<?= $required ?></span>
          </label>
          <p class="c-field__error" id="f-consent-error" hidden></p>
        </div>

        <div class="u-hp" aria-hidden="true">
          <label>この欄は入力しないでください<input type="text" name="<?= e(\Core\Form\Guard::HONEYPOT) ?>" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="p-reserve__submit">
          <button class="c-button c-button--primary c-button--lg c-button--block js-submit" type="submit" disabled>入力内容を確認する<?= lp_icon('arrow', 'c-button__arrow') ?></button>
          <noscript><p class="p-reserve__noscript">フォームのご利用には JavaScript を有効にしてください。お電話（<?= e(site('tel')) ?>）でもご予約いただけます。</p></noscript>
        </div>
      </form>

      <div class="p-reserve__confirm js-confirm" hidden>
        <h3 class="p-reserve__step-title js-step-title" tabindex="-1">入力内容をご確認ください</h3>
        <dl class="p-reserve__confirm-list">
          <div><dt>お名前</dt><dd class="js-confirm-value" data-field="name"></dd></div>
          <div><dt>電話番号</dt><dd class="js-confirm-value" data-field="tel"></dd></div>
          <div><dt>メールアドレス</dt><dd class="js-confirm-value" data-field="email"></dd></div>
          <div><dt>希望部位</dt><dd><span class="js-confirm-value" data-field="parts"></span><span class="p-reserve__estimate js-confirm-estimate" hidden></span></dd></div>
          <div><dt>第1希望日時</dt><dd class="js-confirm-value" data-field="datetime"></dd></div>
          <div><dt>ご質問など</dt><dd class="p-reserve__confirm-message js-confirm-value" data-field="message"></dd></div>
        </dl>
        <p class="p-reserve__send-error js-send-error" role="alert" hidden></p>
<?php if ($static): ?>
        <p class="p-reserve__demo p-reserve__demo--inline"><?= lp_icon('alert') ?><span>サンプルのため、申し込んでも送信されません。</span></p>
<?php endif; ?>
        <div class="p-reserve__actions">
          <button type="button" class="c-button c-button--ghost js-back">修正する</button>
          <button type="button" class="c-button c-button--primary js-send"><span class="js-send-label">この内容で申し込む</span><?= lp_icon('arrow', 'c-button__arrow') ?></button>
        </div>
      </div>

      <div class="p-reserve__complete js-complete" hidden>
        <span class="p-reserve__complete-icon" aria-hidden="true"><?= lp_icon('check') ?></span>
        <h3 class="p-reserve__step-title js-step-title" tabindex="-1">ご予約のお申し込みを受け付けました</h3>
        <p>ご予約はまだ確定していません。2診療日以内に、クリニックからお電話またはメールで、日時を確定するご連絡をいたします。</p>
<?php if ($static): ?>
        <p class="p-reserve__demo"><?= lp_icon('alert') ?><span>サンプルのため送信されません。入力した内容は、どこにも送信・保存されていません。</span></p>
<?php else: ?>
        <p>受付の確認メールをお送りしました。届かない場合は、お手数ですがお電話でお問い合わせください。</p>
<?php endif; ?>
      </div>
    </div>

    <p class="p-reserve__tel"><?= lp_icon('phone') ?><span>お電話でのご予約・ご相談：<a href="<?= e(lp_tel_href()) ?>" data-cta="reserve-tel"><?= e(site('tel')) ?></a>（診療時間内）</span></p>
  </div>
</section>
