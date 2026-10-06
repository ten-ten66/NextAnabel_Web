<?php
/**
 * 下層ページの見出し
 *
 * @var array<string, mixed> $page
 * @var string $kicker   欧文の小見出し（例: Treatment）
 * @var string $title    h1
 * @var ?string $lead    リード文
 * @var ?string $index   大きな数字（Bodoni Moda で表示。数字のみ）
 * @var list<array{0: string, 1: string}> $facts  概要（見出し, 値）
 * @var ?string $reviewed 監修の最終確認日（Y-m-d）。指定すると監修者の表示を出す
 */
$lead ??= null;
$index ??= null;
$facts ??= [];
$reviewed ??= null;
$crumbs = $page['breadcrumb'] ?? [];
?>
<div class="c-page-head">
  <div class="c-page-head__media" aria-hidden="true">
    <img src="<?= e(asset('img/silk-detail.webp')) ?>" width="1200" height="900" alt="" decoding="async" data-parallax>
  </div>
  <div class="l-container c-page-head__inner">
    <?php if ($crumbs): ?>
      <nav class="c-breadcrumb" aria-label="パンくずリスト">
        <ol class="c-breadcrumb__list">
          <?php foreach ($crumbs as $i => $crumb): ?>
            <li class="c-breadcrumb__item">
              <?php if (isset($crumb['href']) && $i < count($crumbs) - 1): ?>
                <a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a>
              <?php else: ?>
                <span aria-current="page"><?= e($crumb['label']) ?></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>
    <div class="c-page-head__body">
      <p class="c-kicker c-page-head__kicker">
        <?php if ($index !== null): ?><span class="c-kicker__index"><?= e($index) ?></span><?php endif; ?>
        <span class="c-kicker__label" lang="en"><?= e($kicker) ?></span>
      </p>
      <h1 class="c-page-head__title" data-split-intro><?= e($title) ?></h1>
      <?php if ($lead !== null): ?>
        <p class="c-page-head__lead"><?= e($lead) ?></p>
      <?php endif; ?>
      <?php if ($reviewed !== null): ?>
        <p class="c-page-head__reviewed" data-reviewed-by>監修：<?= e(sc_reviewer_label()) ?>／最終確認日 <?= sc_date($reviewed) ?></p>
      <?php endif; ?>
    </div>
    <?php if ($facts): ?>
      <dl class="c-page-head__facts">
        <?php foreach ($facts as [$label, $value]): ?>
          <div class="c-page-head__fact">
            <dt><?= e($label) ?></dt>
            <dd><?= e($value) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>
  </div>
</div>
