<?php
/**
 * よくある質問（アコーディオン）
 * 初期状態はすべて開いた状態で出力し、JavaScript が動いたときだけ閉じる（JavaScript なしでも全文が読める）。
 *
 * @var array<string, mixed> $content
 */
?>
<section class="p-faq l-section" id="faq" aria-labelledby="faq-title">
  <div class="l-container p-faq__layout">
    <div class="c-section-head p-faq__head">
      <p class="c-section-head__label"><span class="c-section-head__num">07</span>よくある質問</p>
      <h2 class="c-section-head__title" id="faq-title">ご予約の前に、よくいただく質問。</h2>
      <p class="c-section-head__lead">ほかにも気になることがあれば、カウンセリングで医師とスタッフがお答えします。</p>
    </div>
    <div class="p-faq__list js-faq">
<?php foreach ($content['faq'] as $i => $item): ?>
<?php $n = $i + 1; ?>
      <div class="p-faq__item">
        <h3 class="p-faq__q">
          <button type="button" class="p-faq__toggle js-faq-toggle" id="faq-q<?= $n ?>" aria-expanded="true" aria-controls="faq-a<?= $n ?>">
            <span class="p-faq__mark" aria-hidden="true">Q</span>
            <span class="p-faq__q-text"><?= e($item['q']) ?></span>
            <span class="p-faq__icon" aria-hidden="true"></span>
          </button>
        </h3>
        <div class="p-faq__a" id="faq-a<?= $n ?>">
          <div class="p-faq__a-inner">
            <span class="p-faq__mark p-faq__mark--a" aria-hidden="true">A</span>
            <p><?= lp_text((string) $item['a']) ?></p>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
