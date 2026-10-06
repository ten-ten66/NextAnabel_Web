<?php
/**
 * コース契約のクーリング・オフと中途解約の案内（特定継続的役務に当たるコースがあるページに置く）
 * @var string|null $level 見出しのレベル（h2 / h3）
 * @var string|null $id    見出しの id
 */
$tag = ($level ?? 'h2') === 'h3' ? 'h3' : 'h2';
$headingId = $id ?? 'cooling-off-title';
?>
<section class="c-cooling-off" data-disclosure="cooling-off" aria-labelledby="<?= e($headingId) ?>">
  <<?= $tag ?> class="c-cooling-off__title" id="<?= e($headingId) ?>">コース契約のクーリング・オフと中途解約</<?= $tag ?>>
  <p class="c-cooling-off__lead">期間が1か月を超え、金額が5万円を超えるコース（表の<span class="c-mark" aria-hidden="true">＊</span><span class="u-visually-hidden">「クーリング・オフ対象」</span>印）は、特定商取引法の「特定継続的役務」に当たります。部位などを組み合わせて契約し、合計金額が5万円を超える場合も同じです。</p>
  <dl class="c-cooling-off__list">
    <div class="c-cooling-off__item">
      <dt>クーリング・オフ</dt>
      <dd>契約書面を受け取った日から8日以内であれば、書面または電磁的記録（メールなど）で契約を解除できます。すでに施術を受けていても、その費用をお支払いいただく必要はありません。</dd>
    </div>
    <div class="c-cooling-off__item">
      <dt>中途解約</dt>
      <dd>8日を過ぎた後も、契約期間内であれば中途解約ができます。この場合、受けた施術の料金と、法令で定める上限の範囲内の解約手数料を差し引いて返金します。</dd>
    </div>
  </dl>
</section>
