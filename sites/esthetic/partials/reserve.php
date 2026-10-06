<?php
/**
 * ご予約セクション（電話・Web予約）と、Web予約の説明ダイアログ
 * このパーツを使うページは $page['has_reserve'] = true にする（ヘッダーの「ご予約」がページ内リンクになる）。
 */
?>
<section class="p-reserve" id="reserve" aria-labelledby="reserve-title">
  <div class="l-container">
    <div class="p-reserve__panel">
      <div class="p-reserve__visual" aria-hidden="true">
        <img class="p-reserve__img" src="<?= e(asset('img/leaves-mauve.webp')) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async">
      </div>
      <div class="p-reserve__head">
        <p class="c-section-head__en" lang="en" aria-hidden="true">Reserve</p>
        <h2 class="p-reserve__title" id="reserve-title">ご予約</h2>
        <p class="p-reserve__lead">お電話か、Web予約で承ります。<br class="u-br-md">はじめての方も、どうぞお気軽に。</p>
      </div>
      <div class="p-reserve__ways">
        <div class="p-reserve__way">
          <h3 class="p-reserve__way-title"><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-phone"/></svg>お電話で</h3>
          <p class="p-reserve__tel"><a class="p-reserve__tel-link" href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a></p>
          <p class="p-reserve__note">受付 <?= e(site('hours_label')) ?>（<?= e(site('closed_label')) ?>定休）。施術中は電話に出られないことがあります。留守番電話にお名前を残していただければ、折り返しご連絡します。</p>
        </div>
        <div class="p-reserve__way">
          <h3 class="p-reserve__way-title"><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-clock"/></svg>Webで</h3>
          <p class="p-reserve__action">
            <span class="c-magnet js-magnet">
              <button type="button" class="c-button c-button--primary c-button--lg js-dialog-open" aria-haspopup="dialog" aria-controls="reserve-dialog" hidden>
                <span class="c-button__label">Web予約<span class="c-button__sub">（外部予約システムを想定）</span></span>
                <svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg>
              </button>
            </span>
          </p>
          <p class="p-reserve__note">空き状況の確認から予約・変更までできる、外部の予約システムとの連携を想定しています。このサイトはサンプルのため、予約は受け付けていません。</p>
        </div>
      </div>
    </div>
  </div>
</section>

<dialog class="c-dialog js-dialog" id="reserve-dialog" aria-labelledby="reserve-dialog-title" aria-describedby="reserve-dialog-desc">
  <div class="c-dialog__body">
    <p class="c-dialog__en" lang="en" aria-hidden="true">Reserve</p>
    <h2 class="c-dialog__title" id="reserve-dialog-title">Web予約について</h2>
    <p class="c-dialog__text" id="reserve-dialog-desc">実際のサイトでは、このボタンから外部の予約システムへ移動する想定です。このサイトはWeb制作のサンプルのため、予約は受け付けていません。</p>
    <ul class="c-dialog__list">
      <li>空き状況をカレンダーで確認</li>
      <li>メニュー・担当・オプションを選んで予約</li>
      <li>予約の変更・キャンセル、前日のお知らせメール</li>
    </ul>
    <p class="c-dialog__note">お急ぎの場合は、お電話（<?= e(site('tel')) ?>）でも承ります。</p>
    <div class="c-dialog__actions">
      <button type="button" class="c-button c-button--primary js-dialog-close">閉じる</button>
    </div>
  </div>
  <button type="button" class="c-dialog__close js-dialog-close" aria-label="閉じる"><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-close"/></svg></button>
</dialog>
