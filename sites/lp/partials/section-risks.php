<?php
/**
 * リスク・副作用、ダウンタイム、照射できない方（全文を常に表示し、折りたたまない）
 *
 * @var array<string, mixed> $content
 * @var string $reviewed
 */
?>
<section class="p-risks l-section" id="risks" aria-labelledby="risks-title">
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label c-section-head__label--notice"><span class="c-section-head__num">06</span>リスク・副作用</p>
      <h2 class="c-section-head__title" id="risks-title">施術を受ける前に、必ずお読みください。</h2>
      <p class="c-section-head__lead">医療レーザー脱毛には、次のようなリスク・副作用があります。症状が出たときは、医師が診察して対応します。</p>
    </div>

    <ul class="p-risks__list" data-disclosure="risks">
<?php foreach ($content['risks'] as $risk): ?>
      <li class="p-risks__item">
        <h3 class="p-risks__title"><?= e($risk['title']) ?></h3>
        <p class="p-risks__body"><?= lp_text((string) $risk['body']) ?></p>
      </li>
<?php endforeach; ?>
    </ul>

    <div class="p-risks__downtime" data-disclosure="downtime">
      <h3 class="p-risks__subtitle"><?= lp_icon('clock') ?>ダウンタイム</h3>
      <p><?= lp_text((string) $content['downtime']) ?></p>
    </div>

    <div class="p-risks__contra">
      <section class="p-risks__box" aria-labelledby="contra-title">
        <h3 class="p-risks__subtitle" id="contra-title">照射できない方</h3>
        <ul class="p-risks__bullets">
<?php foreach ($content['contraindications'] as $line): ?>
          <li><?= e($line) ?></li>
<?php endforeach; ?>
        </ul>
      </section>
      <section class="p-risks__box" aria-labelledby="consult-title">
        <h3 class="p-risks__subtitle" id="consult-title">照射の前にご相談いただきたい方</h3>
        <ul class="p-risks__bullets">
<?php foreach ($content['consult'] as $line): ?>
          <li><?= e($line) ?></li>
<?php endforeach; ?>
        </ul>
        <p class="p-risks__box-note">照射できるかどうかは、診察のうえ医師が判断します。</p>
      </section>
    </div>

    <p class="p-risks__review" data-reviewed-by="<?= e(site('reviewer.name')) ?>">このページの医療情報は、<?= e(lp_reviewer_line()) ?>が監修しています（最終確認日 <?= time_tag($reviewed, 'Y年n月j日') ?>）。</p>
  </div>
</section>
