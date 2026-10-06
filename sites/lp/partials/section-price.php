<?php
/**
 * 料金表（静的・データ駆動）＋リスクの併記＋クーリング・オフの案内
 *
 * @var array<string, mixed> $plan
 * @var array<string, mixed> $content
 */
$course = $plan['course'];
$count = (int) $course['count'];
$months = (int) $course['months'];
$shortNames = [];
foreach ($plan['parts'] as $part) {
    $shortNames[(string) $part['id']] = lp_split_label((string) $part['label'])[0];
}
?>
<section class="p-price l-section" id="price" aria-labelledby="price-title">
  <div class="l-container">
    <div class="c-section-head">
      <p class="c-section-head__label"><span class="c-section-head__num">04</span>料金表</p>
      <h2 class="c-section-head__title" id="price-title">料金は、税込の総額で。</h2>
      <p class="c-section-head__lead">表示はすべて税込の総額です。カウンセリング（医師の診察を含む）は無料です。</p>
    </div>

    <div class="p-price__layout" data-disclosure-group>
      <div class="p-price__main" data-disclosure="price">
        <div class="c-table-scroll">
          <table class="c-price-table" data-price-context="tax-included">
            <caption class="c-price-table__caption">医療レーザー脱毛の料金（税込）</caption>
            <thead>
              <tr>
                <th scope="col">部位</th>
                <th scope="col">1回</th>
                <th scope="col"><?= e((string) $count) ?>回コース<span class="c-price-table__th-sub">総額・約<?= e((string) $months) ?>か月</span></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($plan['parts'] as $part): ?>
<?php [$name, $sub] = lp_split_label((string) $part['label']); ?>
<?php $qualifying = lp_is_qualifying_course((int) $part['course'], $months); ?>
              <tr>
                <th scope="row"><?= e($name) ?><?php if ($sub !== ''): ?><span class="c-price-table__sub"><?= e($sub) ?></span><?php endif; ?></th>
                <td><?= e(yen((int) $part['once'])) ?></td>
                <td<?= $qualifying ? ' data-course="qualifying"' : '' ?>><?= e(yen((int) $part['course'])) ?><?php if ($qualifying): ?><span class="c-price-table__mark" aria-hidden="true">※</span><span class="u-visually-hidden">（クーリング・オフの対象）</span><?php endif; ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
            <tbody class="c-price-table__sets">
              <tr>
                <th scope="colgroup" colspan="3" class="c-price-table__group">セット料金</th>
              </tr>
<?php foreach ($plan['sets'] as $set): ?>
<?php $qualifying = lp_is_qualifying_course((int) $set['course'], $months); ?>
              <tr>
<?php [$setName, $setNote] = lp_split_label((string) $set['label']); ?>
                <th scope="row"><?= e($setName) ?><?php if ($setNote !== ''): ?><span class="c-price-table__paren">（<?= e($setNote) ?>）</span><?php endif; ?><span class="c-price-table__sub"><?= e(implode('、', array_map(static fn ($id) => $shortNames[$id] ?? $id, $set['includes']))) ?></span></th>
                <td><?= e(yen((int) $set['once'])) ?></td>
                <td<?= $qualifying ? ' data-course="qualifying"' : '' ?>><?= e(yen((int) $set['course'])) ?><?php if ($qualifying): ?><span class="c-price-table__mark" aria-hidden="true">※</span><span class="u-visually-hidden">（クーリング・オフの対象）</span><?php endif; ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="p-price__table-note">※ のコースは、特定商取引法のクーリング・オフの対象です。複数の部位をまとめて契約し、合計が5万円を超える場合も対象になります。</p>
      </div>

      <aside class="p-price__risks" data-disclosure="risks" aria-labelledby="price-risks-title">
        <p class="p-price__risks-eyebrow"><?= lp_icon('alert') ?>料金とあわせてご確認ください</p>
        <h3 class="p-price__risks-title" id="price-risks-title">主なリスク・副作用</h3>
        <ul class="p-price__risks-list">
<?php foreach ($content['risks'] as $risk): ?>
          <li><?= e($risk['title']) ?></li>
<?php endforeach; ?>
        </ul>
        <p class="p-price__risks-text"><strong>ダウンタイム：</strong>照射後の赤みやほてりは、数時間〜数日で落ち着くことが多いです。</p>
        <p class="p-price__risks-text"><strong>回数の目安：</strong><?= e((string) $count) ?>回・約<?= e((string) $months) ?>か月。必要な回数には個人差があります。</p>
        <p class="p-price__risks-link"><a href="#risks">リスク・副作用と、照射できない方について</a></p>
      </aside>
    </div>

    <div class="p-price__notes">
      <section class="p-price__note" aria-labelledby="included-title">
        <h3 class="p-price__note-title" id="included-title">料金に含まれるもの</h3>
        <ul class="p-price__included">
<?php foreach ($plan['included'] as $item): ?>
          <li><?= lp_icon('check') ?><?= e($item) ?></li>
<?php endforeach; ?>
        </ul>
      </section>
      <section class="p-price__note" aria-labelledby="extra-title">
        <h3 class="p-price__note-title" id="extra-title">別途かかる場合があるもの</h3>
        <dl class="p-price__extra">
<?php foreach ($plan['extra'] as $item): ?>
          <div>
            <dt><?= e($item['label']) ?></dt>
            <dd><?= (int) $item['amount'] > 0 ? e(tax_in((int) $item['amount'])) : e($item['note'] ?? '無料') ?></dd>
          </div>
<?php endforeach; ?>
        </dl>
      </section>
    </div>

    <section class="p-coolingoff" id="cooling-off" data-disclosure="cooling-off" aria-labelledby="cooling-off-title">
      <h3 class="p-coolingoff__title" id="cooling-off-title">クーリング・オフ（契約の解除）について</h3>
      <p>医療レーザー脱毛のコース契約のうち、契約期間が1か月を超え、金額が5万円を超えるものは、特定商取引法の「特定継続的役務」に当たります（美容医療も対象です）。</p>
      <dl class="p-coolingoff__list">
        <div>
          <dt>契約書面を受け取った日から8日以内</dt>
          <dd>書面または電磁的記録（電子メールなど）で、理由を問わず契約を解除できます（クーリング・オフ）。違約金などはかからず、お支払い済みの料金はお返しします。</dd>
        </div>
        <div>
          <dt>8日を過ぎた後</dt>
          <dd>コースの期間中であれば、中途解約ができます。未施術分の料金をお返しし、法令で定める上限の範囲内で解約手数料をいただく場合があります。</dd>
        </div>
      </dl>
      <p class="p-coolingoff__note">詳しい条件は、ご契約の前に書面でご説明します。</p>
    </section>
  </div>
</section>
