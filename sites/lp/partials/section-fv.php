<?php
/**
 * ファーストビュー
 *
 * @var array<string, mixed> $plan
 * @var array<string, mixed> $content
 */
$course = $plan['course'];
?>
<section class="p-fv js-fv" id="fv" aria-labelledby="fv-title">
  <div class="p-fv__bg" aria-hidden="true">
    <img class="p-fv__bg-img" src="<?= e(asset('img/fv-bg.webp')) ?>" alt="" width="1600" height="1000" fetchpriority="high" decoding="async">
    <span class="p-fv__beam"></span>
    <span class="p-fv__beam p-fv__beam--thin"></span>
  </div>
  <div class="l-container p-fv__inner">
    <div class="p-fv__body">
      <p class="p-fv__eyebrow"><?= e(site('name')) ?>の医療レーザー脱毛</p>
      <h1 class="p-fv__title" id="fv-title">
        <span class="p-fv__line">医療脱毛は、</span>
        <span class="p-fv__line"><em class="p-fv__em">総額</em>と<em class="p-fv__em">回数</em>を</span>
        <span class="p-fv__line">知ってから。</span>
      </h1>
      <p class="p-fv__lead">部位を選ぶと、<?= e((string) $course['count']) ?>回コースの税込総額と通院回数の目安がその場でわかります。カウンセリングは無料。その日に契約を決める必要はありません。</p>
      <div class="p-fv__actions">
        <a class="c-button c-button--primary c-button--lg" href="#reserve" data-cta="fv">カウンセリングを予約する（無料）<?= lp_icon('arrow', 'c-button__arrow') ?></a>
        <a class="c-button c-button--ghost c-button--lg" href="#simulator" data-cta="fv-simulator"><?= lp_icon('calc') ?>料金を試算する</a>
      </div>
      <ul class="p-fv__points">
<?php foreach ($content['assurances'] as $item): ?>
        <li class="p-fv__point">
          <span class="p-fv__point-icon"><?= lp_icon((string) $item['icon']) ?></span>
          <span class="p-fv__point-text"><?= e($item['text']) ?><?php if (!empty($item['note'])): ?><span class="p-fv__point-note">（<?= e($item['note']) ?>）</span><?php endif; ?></span>
        </li>
<?php endforeach; ?>
      </ul>
    </div>

    <div class="p-fv__facts">
      <p class="p-fv__facts-title"><?= e((string) $course['count']) ?>回コースの目安</p>
      <dl class="p-fv__facts-list">
        <div class="p-fv__fact">
          <dt>照射回数</dt>
          <dd><span class="p-fv__num"><?= e((string) $course['count']) ?></span><span class="p-fv__unit">回</span></dd>
        </div>
        <div class="p-fv__fact">
          <dt>通院期間</dt>
          <dd><span class="p-fv__unit p-fv__unit--pre">約</span><span class="p-fv__num"><?= e((string) $course['months']) ?></span><span class="p-fv__unit">か月</span></dd>
        </div>
      </dl>
      <ol class="p-fv__timeline" aria-hidden="true">
<?php for ($i = 1; $i <= (int) $course['count']; $i++): ?>
        <li><?= $i ?></li>
<?php endfor; ?>
      </ol>
      <p class="p-fv__facts-note"><?= e($course['interval']) ?>必要な回数には個人差があります。</p>
    </div>
  </div>
</section>
