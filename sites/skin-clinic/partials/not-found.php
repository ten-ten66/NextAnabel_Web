<?php
/** 404 の本文（404.php と、サーバー版で存在しない施術を指定されたときに使う） */
?>
<main id="main">
  <div class="p-404">
    <div class="l-container p-404__inner">
      <p class="p-404__code" aria-hidden="true">404</p>
      <h1 class="p-404__title">お探しのページが見つかりませんでした</h1>
      <p class="p-404__text">ページが移動または削除されたか、URLが間違っている可能性があります。お手数ですが、下のリンクから目的のページをお探しください。</p>
      <ul class="p-404__links">
        <li><a class="c-text-link" href="<?= e(url('index')) ?>">ホーム<?= icon('arrow') ?></a></li>
        <li><a class="c-text-link" href="<?= e(url('treatments')) ?>">施術一覧<?= icon('arrow') ?></a></li>
        <li><a class="c-text-link" href="<?= e(url('price')) ?>">料金表<?= icon('arrow') ?></a></li>
        <li><a class="c-text-link" href="<?= e(url('contact')) ?>">ご予約・お問い合わせ<?= icon('arrow') ?></a></li>
      </ul>
      <p class="p-404__tel">お電話でのお問い合わせ：<a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a>（<?= e(hours_summary()) ?>）</p>
    </div>
  </div>
</main>
