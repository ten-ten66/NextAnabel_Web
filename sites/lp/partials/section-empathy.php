<?php
/**
 * 共感（自己処理の手間と肌への負担）
 *
 * @var array<string, mixed> $content
 */
?>
<section class="p-empathy l-section" id="worries" aria-labelledby="worries-title">
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label"><span class="c-section-head__num">01</span>自己処理の悩み</p>
      <h2 class="c-section-head__title" id="worries-title">自己処理の手間や、肌への負担が気になっていませんか。</h2>
    </div>
    <ul class="p-empathy__list">
<?php foreach ($content['worries'] as $item): ?>
      <li class="p-empathy__item">
        <span class="p-empathy__icon"><?= lp_icon((string) $item['icon']) ?></span>
        <h3 class="p-empathy__title"><?= e($item['title']) ?></h3>
        <p class="p-empathy__body"><?= e($item['body']) ?></p>
      </li>
<?php endforeach; ?>
    </ul>
    <div class="p-empathy__bridge">
      <p>医療レーザー脱毛は、医療機関で医師の管理のもとに行う脱毛です。自己処理の回数を減らしたい方の選択肢のひとつとして、まずは<strong>しくみ</strong>と<strong>費用の全体像</strong>を確かめてください。</p>
    </div>
  </div>
</section>
