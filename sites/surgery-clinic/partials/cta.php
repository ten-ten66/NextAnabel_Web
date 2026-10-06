<?php
/**
 * カウンセリング予約への導線（各ページ共通）
 *
 * @var ?string $heading 見出し（省略時は既定の文言）
 */
$heading ??= 'まずは、ご相談から。';
?>
<section class="p-cta" aria-labelledby="cta-title">
  <div class="p-cta__media" aria-hidden="true">
    <img src="<?= e(asset('img/silk-warm.webp')) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async" data-parallax>
  </div>
  <div class="l-container p-cta__inner">
    <div class="p-cta__head">
      <p class="c-kicker"><span class="c-kicker__label" lang="en">Counseling</span></p>
      <h2 class="p-cta__title" id="cta-title"><?= e($heading) ?></h2>
    </div>
    <div class="p-cta__body">
      <p class="p-cta__text">カウンセリングでは、医師が施術の適応・リスクと副作用・費用の総額をご説明します。手術を受けるかどうかは、お持ち帰りのうえでご検討ください。カウンセリング当日に手術を行うことはありません。</p>
      <div class="p-cta__actions">
        <a class="c-button c-button--large" href="<?= e(url('counseling')) ?>">カウンセリングを予約する<span class="c-button__arrow" aria-hidden="true"></span></a>
        <a class="p-cta__tel" href="<?= e(sc_tel_href()) ?>">
          <span class="p-cta__tel-label">お電話でのご予約</span>
          <span class="p-cta__tel-number"><?= e(site('tel')) ?></span>
        </a>
      </div>
      <dl class="p-cta__facts">
        <div><dt>カウンセリング料</dt><dd><?= e(tax_in((int) site('counseling_fee'))) ?></dd></div>
        <div><dt>所要時間</dt><dd>約<?= e(site('counseling_minutes')) ?>分</dd></div>
        <div><dt>受付</dt><dd><?= e(site('hours_label')) ?>（休診 <?= e(site('closed_label')) ?>）</dd></div>
      </dl>
    </div>
  </div>
</section>
