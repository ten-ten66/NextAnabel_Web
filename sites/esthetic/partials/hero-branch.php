<?php
/**
 * ファーストビューの枝（data/branch.php の座標から描画する装飾）
 *   line  : 線画。読み込み時に線を引くように描く（pathLength="1" で長さを正規化し、長さの計測をしない）
 *   shade : 同じ形の塗り。ぼかして「麻布に落ちた影」として奥のレイヤーに置く
 *
 * @var string $mode 'line' または 'shade'
 */
$branch = require SITE_DIR . '/data/branch.php';
$mode = ($mode ?? 'line') === 'shade' ? 'shade' : 'line';
?>
<svg class="p-hero__branch p-hero__branch--<?= e($mode) ?><?= $mode === 'line' ? ' js-branch' : '' ?>" viewBox="<?= e($branch['viewBox']) ?>" aria-hidden="true" focusable="false" data-intro="draw">
  <?php if ($mode === 'line'): ?>
    <?php foreach ($branch['stems'] as $stem): ?>
      <path class="p-hero__stem js-branch-stem" pathLength="1" data-at="<?= e($stem['at']) ?>" d="<?= e($stem['d']) ?>"/>
    <?php endforeach; ?>
    <?php foreach ($branch['leaves'] as $leaf): ?>
      <g class="p-hero__leaf js-branch-leaf" data-at="<?= e($leaf['at']) ?>">
        <path class="p-hero__leaf-outline" pathLength="1" d="<?= e($leaf['outline']) ?>"/>
        <path class="p-hero__leaf-rib" pathLength="1" d="<?= e($leaf['rib']) ?>"/>
      </g>
    <?php endforeach; ?>
  <?php else: ?>
    <g class="p-hero__shade">
      <?php foreach ($branch['stems'] as $stem): ?>
        <path class="p-hero__shade-stem" d="<?= e($stem['d']) ?>"/>
      <?php endforeach; ?>
      <?php foreach ($branch['leaves'] as $leaf): ?>
        <path d="<?= e($leaf['outline']) ?>"/>
      <?php endforeach; ?>
    </g>
  <?php endif; ?>
</svg>
