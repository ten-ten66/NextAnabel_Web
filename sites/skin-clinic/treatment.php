<?php
require __DIR__ . '/_init.php';

$slug = $_GET['slug'] ?? '';
$t = is_string($slug) ? find_treatment($slug) : null;

if ($t === null) {
    // サーバー版で存在しない slug が指定された場合（静的版では発生しない）
    http_response_code(404);
    $page = ['id' => '404', 'title' => 'ページが見つかりません', 'description' => 'お探しのページは見つかりませんでした。'];
    partial('head', compact('page'));
    partial('header', compact('page'));
    partial('not-found');
    partial('footer', compact('page'));
    return;
}

$cats = categories();
$fees = site_data('fees');
$doctor = site_data('doctor');
$firstVisit = $fees['basic'][0];
$anesthesiaFee = $fees['basic'][2];
$hair = ($t['price_table'] ?? '') === 'hair_removal' ? site_data('hair_removal') : null;

// 特定継続的役務（期間1か月超かつ5万円超）に当たるコースがあるか
$hasQualifying = false;
foreach ($t['courses'] as $course) {
    $hasQualifying = $hasQualifying || is_qualifying_course($course['months'], $course['amount']);
}
if ($hair !== null) {
    foreach (array_merge($hair['parts'], $hair['sets']) as $row) {
        $hasQualifying = $hasQualifying || is_qualifying_course($hair['course']['months'], $row['course']);
    }
}

// 同じ悩みに対応する施術を先に、足りなければほかの施術で補う（最大3件）
$related = [];
foreach (treatments() as $other) {
    if ($other['slug'] !== $t['slug'] && array_intersect($other['categories'], $t['categories'])) {
        $related[] = $other;
    }
}
foreach (treatments() as $other) {
    if (count($related) >= 3) {
        break;
    }
    if ($other['slug'] !== $t['slug'] && !in_array($other, $related, true)) {
        $related[] = $other;
    }
}

$toc = [
    'for' => 'こんな方に',
    'flow' => '施術の流れ',
    'cost' => '費用と主なリスク・副作用',
];
if (!empty($t['unapproved'])) {
    $toc['unapproved'] = '未承認医療機器に関する表記';
}
$toc['course'] = '回数・期間とダウンタイム';
if ($hasQualifying) {
    $toc['cooling-off'] = 'クーリング・オフと中途解約';
}
$toc['contraindications'] = '受けられない方';
$toc['aftercare'] = 'アフターケア';
$toc['contact'] = 'ご予約・お問い合わせ';

$description = $t['name'] . 'の費用（税込）、回数・期間の目安、主なリスク・副作用、ダウンタイムについて、皮膚科専門医の監修のもとでご説明します。南青山の美容皮膚科、' . site('name') . '。';
$page = [
    'id' => 'treatment',
    'section' => 'treatments',
    'slug' => $t['slug'],
    'title' => $t['name'],
    'description' => $description,
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '施術一覧', 'href' => url('treatments')],
        ['label' => $t['name']],
    ],
    'jsonld' => [medical_webpage_ld($t, $description), business_ld()],
];
partial('head', compact('page'));
partial('header', compact('page'));
$unapprovedLabels = [
    'status' => '未承認医療機器であること',
    'route' => '入手経路',
    'domestic' => '国内の承認医療機器の有無',
    'overseas' => '諸外国における安全性などの情報',
    'relief' => '医薬品副作用被害救済制度について',
];
?>
<main id="main">
  <div class="p-tx-hero">
    <div class="l-container">
      <?php partial('breadcrumb', ['items' => $page['breadcrumb']]); ?>
      <div class="p-tx-hero__grid">
        <div class="p-tx-hero__body">
          <p class="p-tx-hero__tags">
            <?php foreach ($t['categories'] as $id): ?>
              <span class="c-tag"><?= e($cats[$id]['label'] ?? '') ?></span>
            <?php endforeach; ?>
          </p>
          <h1 class="p-tx-hero__title"><?= name_html($t['name']) ?></h1>
          <p class="p-tx-hero__en" aria-hidden="true"><?= e($t['en']) ?></p>
          <p class="p-tx-hero__summary"><?= e($t['summary']) ?></p>
          <dl class="p-tx-hero__facts">
            <div class="p-tx-hero__fact"><dt>施術時間</dt><dd><?= e($t['duration']) ?></dd></div>
            <div class="p-tx-hero__fact"><dt>回数の目安</dt><dd><?= e($t['sessions_short']) ?></dd></div>
            <div class="p-tx-hero__fact"><dt>ダウンタイム</dt><dd><?= e($t['downtime_short']) ?></dd></div>
            <div class="p-tx-hero__fact"><dt>麻酔</dt><dd><?= e($t['anesthesia']) ?></dd></div>
          </dl>
          <p class="c-reviewed" data-reviewed-by>監修：<?= e(reviewer_label()) ?>／最終確認日 <?= ja_time_tag($t['reviewed']) ?></p>
        </div>
        <div class="p-tx-hero__visual<?= !empty($t['mirror']) ? ' is-mirrored' : '' ?>">
          <img src="<?= e(asset($t['image'])) ?>" width="960" height="720" alt="" fetchpriority="high">
        </div>
      </div>
    </div>
  </div>

  <div class="l-container p-tx-layout">
    <nav class="p-tx-toc" aria-label="このページの目次">
      <p class="p-tx-toc__title">このページの内容</p>
      <ul class="p-tx-toc__list">
        <?php foreach ($toc as $anchor => $label): ?>
          <li><a class="p-tx-toc__link" href="#<?= e($anchor) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="p-tx-content">
      <section class="p-tx-section" id="for" aria-labelledby="for-title">
        <h2 class="p-tx-section__title" id="for-title">こんな方に</h2>
        <ul class="c-check-list c-check-list--large">
          <?php foreach ($t['for'] as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="c-note">症状の原因によっては、ほかの治療が適している場合があります。診察のうえでご提案します。</p>
      </section>

      <section class="p-tx-section" id="flow" aria-labelledby="flow-title">
        <h2 class="p-tx-section__title" id="flow-title">施術の流れ</h2>
        <ol class="p-tx-flow">
          <?php foreach ($t['flow'] as $i => $step): ?>
            <?php [$stepTitle, $stepText] = split_heading($step); ?>
            <li class="p-tx-flow__step">
              <span class="p-tx-flow__num" aria-hidden="true"><?= e(sprintf('%02d', $i + 1)) ?></span>
              <?php if ($stepTitle !== ''): ?>
                <h3 class="p-tx-flow__title"><?= e($stepTitle) ?></h3>
              <?php endif; ?>
              <p class="p-tx-flow__text"><?= e($stepText) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <section class="p-tx-section" id="cost" aria-labelledby="cost-title">
        <h2 class="p-tx-section__title" id="cost-title">費用と主なリスク・副作用</h2>
        <p class="p-tx-section__lead">費用とあわせて、起こりうるリスク・副作用をご確認ください。</p>
        <div class="p-tx-disclosure<?= $hair !== null ? ' p-tx-disclosure--wide' : '' ?>" data-disclosure-group>
          <div class="p-tx-disclosure__price" data-disclosure="price">
            <h3 class="p-tx-disclosure__title">費用（税込）</h3>
            <?php if ($hair !== null): ?>
              <?php partial('hair-price-tables'); ?>
              <ul class="c-note-list">
                <li><?= e($hair['course']['count']) ?>回コースの期間の目安は<?= e($hair['course']['months']) ?>か月です。<?= e($hair['course']['interval']) ?></li>
                <li>料金に含まれるもの：<?= e(implode('、', $hair['included'])) ?></li>
                <?php foreach ($hair['extra'] as $extra): ?>
                  <?php if ($extra['amount'] > 0): ?>
                    <li><?= e($extra['label']) ?>：<?= price_html($extra['amount']) ?></li>
                  <?php else: ?>
                    <li><?= e($extra['label']) ?>：<?= e($extra['note'] ?? '') ?></li>
                  <?php endif; ?>
                <?php endforeach; ?>
                <li>このほかに、初診料 <?= price_html($firstVisit['amount']) ?>がかかります。</li>
              </ul>
            <?php else: ?>
              <?php partial('price-table', ['t' => $t]); ?>
              <ul class="c-note-list">
                <?php if (!empty($t['price_note'])): ?>
                  <li><?= e($t['price_note']) ?></li>
                <?php endif; ?>
                <?php if (!empty($t['anesthesia_extra'])): ?>
                  <li><?= e($anesthesiaFee['label']) ?>：<?= price_html($anesthesiaFee['amount']) ?></li>
                <?php endif; ?>
                <li>このほかに、初診料 <?= price_html($firstVisit['amount']) ?>がかかります。</li>
              </ul>
            <?php endif; ?>
            <a class="c-text-link" href="<?= e(url('price#price-' . $t['category'])) ?>">料金表でほかの施術と比べる<?= icon('arrow') ?></a>
          </div>
          <div class="p-tx-disclosure__risks" data-disclosure="risks">
            <div class="p-tx-disclosure__inner">
              <h3 class="p-tx-disclosure__title p-tx-disclosure__title--notice">主なリスク・副作用</h3>
              <ul class="c-risk-list">
                <?php foreach ($t['risks'] as $risk): ?>
                  <li><?= e($risk) ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="c-note">症状が出た場合は、診察のうえで必要な処置を行います。気になる変化があれば、施術日以外でもご連絡ください。</p>
            </div>
          </div>
        </div>
      </section>

      <?php if (!empty($t['unapproved'])): ?>
        <section class="c-unapproved" id="unapproved" data-unapproved aria-labelledby="unapproved-title">
          <h2 class="c-unapproved__title" id="unapproved-title">未承認医療機器に関する表記（サンプルの記載例）</h2>
          <p class="c-unapproved__lead">未承認の医療機器を使う施術で表示が必要な5項目の記載例です。このサイトは架空のサンプルで、実在の機器の承認状況を示すものではありません。</p>
          <dl class="c-unapproved__list">
            <?php foreach ($unapprovedLabels as $key => $label): ?>
              <div class="c-unapproved__item" data-unapproved-item="<?= e($key) ?>">
                <dt><?= e($label) ?></dt>
                <dd><?= e($t['unapproved_info'][$key]) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </section>
      <?php endif; ?>

      <section class="p-tx-section" id="course" aria-labelledby="course-title">
        <h2 class="p-tx-section__title" id="course-title">回数・期間とダウンタイム</h2>
        <div class="p-tx-facts">
          <div class="p-tx-fact" data-disclosure="sessions">
            <h3 class="p-tx-fact__title">回数・期間の目安</h3>
            <p class="p-tx-fact__text"><?= e($t['sessions']) ?></p>
          </div>
          <div class="p-tx-fact" data-disclosure="downtime">
            <h3 class="p-tx-fact__title">ダウンタイム</h3>
            <p class="p-tx-fact__text"><?= e($t['downtime']) ?></p>
          </div>
          <div class="p-tx-fact">
            <h3 class="p-tx-fact__title">施術時間・麻酔</h3>
            <dl class="p-tx-fact__list">
              <div><dt>施術時間</dt><dd><?= e($t['duration']) ?></dd></div>
              <div><dt>麻酔</dt><dd><?= e($t['anesthesia']) ?></dd></div>
            </dl>
          </div>
        </div>
      </section>

      <?php if ($hasQualifying): ?>
        <div id="cooling-off" class="p-tx-anchor">
          <?php partial('cooling-off', ['level' => 'h2']); ?>
        </div>
      <?php endif; ?>

      <section class="p-tx-section" id="contraindications" aria-labelledby="contraindications-title">
        <h2 class="p-tx-section__title" id="contraindications-title">受けられない方</h2>
        <ul class="c-dash-list c-dash-list--large">
          <?php foreach ($t['contraindications'] as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="c-note">このほか、持病や服用中のお薬によっては施術をお控えいただく場合があります。問診票に、できるだけ詳しくご記入ください。</p>
      </section>

      <section class="p-tx-section" id="aftercare" aria-labelledby="aftercare-title">
        <h2 class="p-tx-section__title" id="aftercare-title">アフターケア</h2>
        <ul class="c-check-list c-check-list--large">
          <?php foreach ($t['aftercare'] as $item): ?>
            <li><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section class="p-tx-contact" id="contact" data-disclosure="contact" aria-labelledby="contact-title">
        <h2 class="p-tx-contact__title" id="contact-title">ご予約・お問い合わせ</h2>
        <p class="p-tx-contact__text"><?= e($t['name']) ?>についてのご相談・ご予約は、Webフォームまたはお電話で承ります。カウンセリングでは、費用とリスクをご説明したうえで、施術を受けるかどうかをご検討いただけます。</p>
        <div class="p-tx-contact__actions">
          <a class="c-button c-button--primary" href="<?= e(url('contact') . '?menu=' . rawurlencode($t['slug'])) ?>">この施術についてWebで予約する<?= icon('arrow') ?></a>
          <a class="p-tx-contact__tel" href="<?= e(tel_href()) ?>"><?= icon('tel') ?><span class="p-tx-contact__tel-number"><?= e(site('tel')) ?></span></a>
        </div>
        <dl class="p-tx-contact__info">
          <div><dt>電話受付</dt><dd><?= e(hours_summary()) ?></dd></div>
          <div><dt>休診日</dt><dd><?= e(site('closed_label')) ?></dd></div>
          <div><dt>所在地</dt><dd>〒<?= e(site('address.postal_code')) ?> <?= e(full_address()) ?></dd></div>
        </dl>
      </section>

      <aside class="p-tx-review" aria-label="このページの監修">
        <p class="p-tx-review__label">このページの監修</p>
        <p class="p-tx-review__name"><?= e($doctor['role']) ?> <?= e($doctor['name']) ?><span class="p-tx-review__spec">（<?= e($doctor['specialty']) ?>）</span></p>
        <p class="p-tx-review__text">掲載内容は、最終確認日 <?= ja_time_tag($t['reviewed']) ?> 時点のものです。料金や施術の内容は変更になる場合があります。</p>
        <a class="c-text-link" href="<?= e(url('doctor')) ?>">医師紹介を見る<?= icon('arrow') ?></a>
      </aside>
    </div>
  </div>

  <section class="l-section l-section--tint p-tx-related" aria-labelledby="related-title">
    <div class="l-container">
      <div class="p-tx-related__head">
        <h2 class="p-tx-related__title" id="related-title">ほかの施術</h2>
        <a class="c-text-link" href="<?= e(url('treatments')) ?>">施術一覧へ<?= icon('arrow') ?></a>
      </div>
      <ul class="c-tx-grid c-tx-grid--related">
        <?php foreach ($related as $other): ?>
          <?php partial('tx-card', ['t' => $other]); ?>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
