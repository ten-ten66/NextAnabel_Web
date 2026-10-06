<?php
// treatment.php から読み込まれる場合があるため require_once にする
require_once __DIR__ . '/_init.php';

if (!is_static()) {
    http_response_code(404);
}

$page = [
    'id' => 'not-found',
    'title' => 'ページが見つかりません',
    'description' => 'お探しのページは、移動または削除された可能性があります。オルヴァン美容外科のトップページ・施術メニュー・カウンセリング予約のページからお探しください。',
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main" class="p-not-found">
  <div class="l-container p-not-found__inner">
    <p class="c-numeral p-not-found__code" aria-hidden="true">404</p>
    <div class="p-not-found__body">
      <p class="c-kicker"><span class="c-kicker__label" lang="en">Page not found</span></p>
      <h1 class="p-not-found__title" data-split-intro>ページが見つかりません</h1>
      <p class="p-not-found__text">お探しのページは、移動または削除された可能性があります。URLに誤りがないかご確認いただくか、以下のページからお探しください。</p>
      <ul class="p-not-found__links">
        <li><a class="c-link" href="<?= e(url('index')) ?>">トップページ</a></li>
        <li><a class="c-link" href="<?= e(url('index#treatments')) ?>">施術メニュー</a></li>
        <li><a class="c-link" href="<?= e(url('counseling')) ?>">カウンセリング予約</a></li>
      </ul>
    </div>
  </div>
</main>
<?php partial('footer'); ?>
