<?php
/**
 * 施術の流れ
 *
 * @var array<string, mixed> $content
 */
?>
<section class="p-flow l-section" id="flow" aria-labelledby="flow-title">
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label"><span class="c-section-head__num">05</span>施術の流れ</p>
      <h2 class="c-section-head__title" id="flow-title">ご予約から照射まで、4つのステップ。</h2>
    </div>
    <ol class="p-flow__list">
<?php foreach ($content['flow'] as $i => $step): ?>
      <li class="p-flow__item">
        <div class="p-flow__head">
          <span class="p-flow__num" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
          <span class="p-flow__icon"><?= lp_icon((string) $step['icon']) ?></span>
        </div>
        <h3 class="p-flow__title"><?= e($step['title']) ?></h3>
        <p class="p-flow__body"><?= e($step['body']) ?></p>
      </li>
<?php endforeach; ?>
    </ol>
  </div>
</section>
