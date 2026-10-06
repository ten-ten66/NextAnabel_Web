<?php
/**
 * @var string $reviewed 監修医の最終確認日（Y-m-d）
 */
?>
<footer class="l-footer">
  <div class="l-container l-footer__inner">
    <div class="l-footer__clinic" data-disclosure="contact">
      <p class="l-footer__name"><?= e(site('name')) ?><span class="l-footer__name-en" lang="en"><?= e(site('name_en')) ?></span></p>
      <address class="l-footer__address">
        <span class="l-footer__row"><?= lp_icon('pin') ?><span>〒<?= e(site('address.postal_code')) ?> <?= e(site('address.region') . site('address.locality') . site('address.street')) ?></span></span>
        <span class="l-footer__row"><?= lp_icon('phone') ?><span>TEL <a href="<?= e(lp_tel_href()) ?>" data-cta="footer-tel"><?= e(site('tel')) ?></a>（ご予約・お問い合わせ）</span></span>
      </address>
      <ul class="l-footer__access">
        <?php foreach ((array) site('access', []) as $line): ?>
        <li><?= e($line) ?></li>
        <?php endforeach; ?>
      </ul>
      <dl class="l-footer__hours">
        <?php foreach (lp_hours_rows() as $row): ?>
        <div><dt><?= e($row['days']) ?></dt><dd><?= e($row['time']) ?></dd></div>
        <?php endforeach; ?>
        <div><dt>休診日</dt><dd><?= e(site('closed_label')) ?></dd></div>
      </dl>
    </div>

    <nav class="l-footer__nav" aria-label="ページ内のご案内">
      <ul>
        <li><a href="#simulator">料金シミュレーター</a></li>
        <li><a href="#price">料金表</a></li>
        <li><a href="#risks">リスク・副作用</a></li>
        <li><a href="#faq">よくある質問</a></li>
        <li><a href="#reserve">カウンセリングの予約</a></li>
        <li><a href="<?= e(lp_corporate_url()) ?>" target="_blank" rel="noopener"><?= e(site('name')) ?> 公式サイト<?= lp_icon('external', 'l-footer__external') ?><span class="u-visually-hidden">（新しいタブで開きます）</span></a></li>
      </ul>
    </nav>

    <div class="l-footer__meta">
      <p class="l-footer__review" data-reviewed-by="<?= e(site('reviewer.name')) ?>">監修：<?= e(lp_reviewer_line()) ?>／最終確認日 <?= time_tag($reviewed, 'Y年n月j日') ?></p>
      <p class="l-footer__policy">このページは、医療広告ガイドラインを踏まえた設計で作成しています。表示している料金はすべて税込の総額です。</p>
      <p class="l-footer__demo" data-lint-ignore>このページは、Web制作のサンプルとして作成した架空のクリニックの広告用ランディングページです。実在の医療機関・人物とは関係ありません。<?= is_static() ? 'フォームに入力した内容は送信されません。' : '' ?></p>
      <p class="l-footer__copy"><small>&copy; <?= e(site('name')) ?>（架空）</small></p>
    </div>
  </div>
</footer>

<div class="p-dock js-dock" hidden>
  <div class="p-dock__inner">
    <p class="p-dock__summary js-dock-summary" hidden>
      <span class="p-dock__summary-label">試算</span>
      <span class="js-dock-parts">0部位</span>
      <span class="p-dock__summary-total"><?= e((string) lp_plan()['course']['count']) ?>回コース総額 <strong class="js-dock-total">0円</strong>（税込）</span>
    </p>
    <div class="p-dock__actions">
      <a class="p-dock__tel" href="<?= e(lp_tel_href()) ?>" data-cta="dock-tel"><?= lp_icon('phone') ?><span>電話</span></a>
      <a class="c-button c-button--primary p-dock__cta" href="#reserve" data-cta="dock">カウンセリングを予約（無料）<?= lp_icon('arrow') ?></a>
    </div>
  </div>
</div>
<div class="c-toast js-toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
