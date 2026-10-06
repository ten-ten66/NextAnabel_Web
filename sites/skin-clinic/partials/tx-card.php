<?php
/**
 * 施術カード（トップ・施術一覧で共通。絞り込みの対象）
 * @var array<string, mixed> $t
 * @var string|null $variant card（既定）/ row
 */
$variant = ($variant ?? 'card') === 'row' ? 'row' : 'card';
$cats = categories();
$first = $t['prices'][0];
?>
<li class="c-tx-card c-tx-card--<?= e($variant) ?> js-filter-item" data-categories="<?= e(implode(' ', $t['categories'])) ?>">
  <div class="c-tx-card__visual<?= !empty($t['mirror']) ? ' is-mirrored' : '' ?>">
    <img src="<?= e(asset($t['image'])) ?>" width="960" height="720" alt="" loading="lazy" decoding="async">
  </div>
  <div class="c-tx-card__body">
    <h3 class="c-tx-card__title"><a class="c-tx-card__link" href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"><?= name_html($t['name']) ?></a></h3>
    <p class="c-tx-card__tags">
      <?php foreach ($t['categories'] as $id): ?>
        <span class="c-tag"><?= e($cats[$id]['label'] ?? '') ?></span>
      <?php endforeach; ?>
    </p>
    <p class="c-tx-card__lead"><?= e($t['lead']) ?></p>
    <dl class="c-tx-card__facts">
      <div class="c-tx-card__fact">
        <dt>料金</dt>
        <dd><?= e($first['label']) ?> <?= price_html($first['amount']) ?></dd>
      </div>
      <?php if ($variant === 'row'): ?>
        <div class="c-tx-card__fact">
          <dt>回数の目安</dt>
          <dd><?= e($t['sessions_short']) ?></dd>
        </div>
        <div class="c-tx-card__fact">
          <dt>施術時間</dt>
          <dd><?= e($t['duration']) ?></dd>
        </div>
      <?php endif; ?>
      <div class="c-tx-card__fact">
        <dt>ダウンタイム</dt>
        <dd><?= e($t['downtime_short']) ?></dd>
      </div>
      <div class="c-tx-card__fact">
        <dt>主なリスク</dt>
        <dd><?= e($t['risks_short']) ?></dd>
      </div>
    </dl>
    <span class="c-tx-card__more" aria-hidden="true">詳しく見る<?= icon('arrow') ?></span>
  </div>
</li>
