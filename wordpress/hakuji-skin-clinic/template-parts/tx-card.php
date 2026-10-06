<?php
/**
 * 施術カード（トップ・施術一覧で共通。絞り込みの対象）
 *
 * @var array{t: array<string, mixed>} $args
 */
$t = $args['t'];
$first = $t['prices'][0] ?? null;
?>
<li class="c-tx-card c-tx-card--card js-filter-item" data-categories="<?php echo esc_attr(implode(' ', $t['categories'])); ?>">
  <div class="c-tx-card__visual">
    <img src="<?php echo esc_url(HAKUJI_URI . '/assets/img/' . $t['image']); ?>" width="960" height="720" alt="" loading="lazy" decoding="async">
  </div>
  <div class="c-tx-card__body">
    <h3 class="c-tx-card__title"><a class="c-tx-card__link" href="<?php echo esc_url($t['url']); ?>"><?php echo hakuji_name_html($t['name']); ?></a></h3>
    <p class="c-tx-card__tags">
      <?php foreach ($t['category_labels'] as $label) : ?>
        <span class="c-tag"><?php echo esc_html($label); ?></span>
      <?php endforeach; ?>
    </p>
    <p class="c-tx-card__lead"><?php echo esc_html($t['lead']); ?></p>
    <dl class="c-tx-card__facts">
      <?php if ($first) : ?>
        <div class="c-tx-card__fact">
          <dt>料金</dt>
          <dd><?php echo esc_html($first['label']); ?> <?php echo hakuji_price_html($first['amount']); ?></dd>
        </div>
      <?php endif; ?>
      <div class="c-tx-card__fact">
        <dt>ダウンタイム</dt>
        <dd><?php echo esc_html($t['downtime_short']); ?></dd>
      </div>
      <div class="c-tx-card__fact">
        <dt>主なリスク</dt>
        <dd><?php echo esc_html($t['risks_short']); ?></dd>
      </div>
    </dl>
    <span class="c-tx-card__more" aria-hidden="true">詳しく見る<?php echo hakuji_icon('arrow'); ?></span>
  </div>
</li>
