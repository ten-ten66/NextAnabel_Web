<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => '404',
    'title' => 'ページが見つかりません',
    'description' => 'お探しのページは見つかりませんでした。苔と麻のトップページ、またはメニューと料金のページからお探しください。',
    'bodyClass' => 'is-notfound',
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main" class="p-notfound">
  <div class="l-container p-notfound__inner">
    <svg class="p-notfound__sprig" viewBox="0 0 160 200" aria-hidden="true" focusable="false">
      <path class="p-notfound__stem" pathLength="1" d="M80 196c-2-40 4-78 0-118-2-22-10-40-22-56"/>
      <path class="p-notfound__leaf" pathLength="1" d="M80 132c-22 2-38-12-42-34 22-2 38 12 42 34Z"/>
      <path class="p-notfound__leaf" pathLength="1" d="M81 104c2-24 18-40 42-42 0 24-16 40-42 42Z"/>
      <path class="p-notfound__leaf" pathLength="1" d="M70 52c-16-6-24-20-22-36 16 6 24 20 22 36Z"/>
      <circle class="p-notfound__dew" cx="112" cy="150" r="4"/>
    </svg>
    <p class="c-section-head__en" lang="en" aria-hidden="true">Not found</p>
    <h1 class="p-notfound__title">お探しのページは<br>見つかりませんでした</h1>
    <p class="p-notfound__text">苔のすきまに、迷いこんでしまったようです。<br class="u-br-md">ページが移動したか、URLが変わった可能性があります。</p>
    <ul class="p-notfound__links">
      <li><a class="c-button c-button--primary" href="<?= e(url('index')) ?>">トップへ戻る<svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a></li>
      <li><a class="c-button c-button--ghost" href="<?= e(url('menu')) ?>">メニューと料金</a></li>
    </ul>
  </div>
</main>
<?php partial('footer', compact('page')); ?>
