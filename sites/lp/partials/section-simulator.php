<?php
/**
 * 料金シミュレーター
 *
 * 部位のチェックボックス一覧が操作の正（キーボード・読み上げ・JavaScript なしでも使える）。
 * 人体図は一覧と双方向に同期する補助の入力で、JavaScript が動くときだけボタンとして機能する。
 * 計算に使う料金データは、下の料金表と同じ hair_removal.php から #plan-data に JSON で埋め込む。
 *
 * @var array<string, mixed> $plan
 * @var array<string, mixed> $content
 */
$course = $plan['course'];
$count = (int) $course['count'];
$riskNames = array_column($content['risks'], 'title');
$planJson = json_encode(
    [
        'course' => $course,
        'parts' => $plan['parts'],
        'sets' => $plan['sets'],
        'coolingOff' => ['amount' => LP_COOLING_OFF_AMOUNT, 'months' => LP_COOLING_OFF_MONTHS],
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$groups = ['front' => '前面', 'back' => '背面'];
?>
<section class="p-sim l-section" id="simulator" aria-labelledby="simulator-title">
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label"><span class="c-section-head__num">03</span>料金シミュレーター</p>
      <h2 class="c-section-head__title" id="simulator-title">部位を選んで、総額を試算する。</h2>
      <p class="c-section-head__lead">人体図か一覧から部位を選ぶと、<?= e((string) $count) ?>回コースの税込総額、1回あたりの料金、通院回数・期間の目安を表示します。セットに含まれる部位をすべて選ぶと、セット料金で計算します。</p>
    </div>

    <div class="p-sim__grid js-sim">
      <div class="p-sim__stage">
        <div class="p-sim__toggle js-sim-toggle" role="group" aria-label="人体図の向き" hidden>
<?php foreach ($groups as $side => $label): ?>
          <button type="button" class="p-sim__toggle-btn js-sim-view-btn" data-view="<?= e($side) ?>" aria-pressed="<?= $side === 'front' ? 'true' : 'false' ?>"><?= e($label) ?><span class="p-sim__toggle-count js-sim-side-count" data-side="<?= e($side) ?>"></span></button>
<?php endforeach; ?>
        </div>
        <div class="p-sim__views js-sim-views">
<?php foreach ($groups as $side => $label): ?>
          <div class="p-sim__view js-sim-view" data-view="<?= e($side) ?>">
            <?php partial('figure', ['side' => $side]); ?>
            <p class="p-sim__view-label"><?= e($label) ?></p>
          </div>
<?php endforeach; ?>
        </div>
        <p class="p-sim__readout js-sim-readout" aria-hidden="true" hidden>人体図の部位をタップして選べます</p>
      </div>

      <fieldset class="p-sim__list">
        <legend class="p-sim__legend">部位を選ぶ<span class="p-sim__legend-sub">（複数選択できます）</span></legend>
        <div class="p-sim__sets js-sim-sets" hidden>
          <span class="p-sim__sets-label">セットで選ぶ</span>
<?php foreach ($plan['sets'] as $set): ?>
          <button type="button" class="c-chip js-sim-set" data-set="<?= e($set['id']) ?>"><?= e($set['label']) ?></button>
<?php endforeach; ?>
          <button type="button" class="c-textbutton js-sim-clear">選択をすべて解除</button>
        </div>
<?php foreach ($groups as $side => $label): ?>
        <p class="p-sim__group-label"><?= e($label) ?></p>
        <ul class="p-sim__options" data-price-context="tax-included">
<?php foreach ($plan['parts'] as $part): ?>
<?php if ($part['side'] !== $side) {
    continue;
} ?>
<?php [$name, $sub] = lp_split_label((string) $part['label']); ?>
          <li>
            <label class="p-sim__option">
              <input class="p-sim__check js-sim-check" type="checkbox" value="<?= e($part['id']) ?>" id="sim-<?= e($part['id']) ?>">
              <span class="p-sim__box" aria-hidden="true"><?= lp_icon('check') ?></span>
              <span class="p-sim__option-body">
                <span class="p-sim__option-name"><?= e($name) ?></span>
<?php if ($sub !== ''): ?>
                <span class="p-sim__option-sub"><?= e($sub) ?></span>
<?php endif; ?>
              </span>
              <span class="p-sim__option-price"><?= e((string) $count) ?>回 <?= e(yen((int) $part['course'])) ?></span>
            </label>
          </li>
<?php endforeach; ?>
        </ul>
<?php endforeach; ?>
        <p class="p-sim__list-note">一覧の料金は、<?= e((string) $count) ?>回コースの総額（税込）です。</p>
      </fieldset>

      <div class="p-sim__result" data-price-context="tax-included">
        <div class="p-sim__card js-sim-result">
          <p class="p-sim__selected js-sim-selected">部位が選ばれていません</p>
          <p class="p-sim__total-label"><?= e((string) $count) ?>回コース総額（税込）</p>
          <p class="p-sim__total is-empty js-sim-total-wrap"><span class="p-sim__total-num js-sim-total" aria-hidden="true">0</span><span class="p-sim__total-unit">円</span><span class="p-sim__total-tax">（税込）</span></p>
          <p class="u-visually-hidden js-sim-live" aria-live="polite"></p>
          <p class="p-sim__set-note js-sim-set-note" hidden><?= lp_icon('check') ?><span>セット料金で計算しています（<span class="js-sim-set-label"></span>）</span></p>

          <dl class="p-sim__breakdown">
            <div class="p-sim__row">
              <dt>1回あたりの料金（税込）<span class="p-sim__row-sub">コースにせず1回ずつ受ける場合</span></dt>
              <dd><span class="js-sim-once">—</span><span class="js-sim-once-unit" hidden>円（税込）</span></dd>
            </div>
            <div class="p-sim__row">
              <dt>通院回数・期間の目安</dt>
              <dd><?= e((string) $count) ?>回・約<?= e((string) $course['months']) ?>か月</dd>
            </div>
          </dl>
          <p class="p-sim__note"><?= e($course['interval']) ?>必要な回数には個人差があります。照射する範囲と回数は、診察のうえ医師が判断します。</p>

          <p class="p-sim__coolingoff js-sim-coolingoff" hidden><?= lp_icon('alert') ?><span>この金額のコース契約は、クーリング・オフの対象です。契約書面を受け取った日から8日以内であれば、書面または電磁的記録で解除できます。<a href="#cooling-off">契約の解除について</a></span></p>
          <p class="p-sim__risks" data-disclosure="risks"><span class="p-sim__risks-label">主なリスク・副作用</span><?= e(implode('、', $riskNames)) ?>（<a href="#risks">くわしく見る</a>）</p>

          <a class="c-button c-button--primary c-button--block js-sim-reserve" href="#reserve" data-cta="simulator">この内容でカウンセリングを予約<?= lp_icon('arrow', 'c-button__arrow') ?></a>
          <noscript><p class="p-sim__noscript">JavaScript が無効のため、試算結果を表示できません。下の料金表をご覧ください。</p></noscript>
        </div>
      </div>
    </div>
    <script type="application/json" id="plan-data"><?= $planJson ?></script>
  </div>
</section>
