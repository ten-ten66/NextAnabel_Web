<?php
require_once __DIR__ . '/_init.php';

$slug = $_GET['slug'] ?? '';
$t = is_string($slug) ? sc_treatment($slug) : null;
if ($t === null) {
    // サーバー版で存在しない施術が指定されたときは 404 を返す
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$treatments = sc_treatments();
$number = (int) array_search($t['slug'], array_column($treatments, 'slug'), true) + 1;
$bodyLocations = ['eyes' => '上まぶた', 'face' => '頬・フェイスライン', 'body' => '腹部・太もも'];
$description = $t['name'] . 'の費用（税込）・主なリスクと副作用・ダウンタイム・施術の流れ・術後の注意をご説明します。' . $t['lead'];
$flowTexts = array_map(static fn (string $step): string => implode('：', array_filter(sc_split_step($step))), $t['flow']);

$page = [
    'id' => 'treatment',
    'title' => $t['name'],
    'description' => $description,
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '施術メニュー', 'href' => url('index#treatments')],
        ['label' => $t['name']],
    ],
    'jsonld' => [
        business_ld(),
        [
            '@type' => 'MedicalWebPage',
            '@id' => absolute_url() . '#webpage',
            'url' => absolute_url(),
            'name' => $t['name'],
            'description' => $description,
            'inLanguage' => 'ja',
            'publisher' => ['@id' => absolute_url('') . '#business'],
            'specialty' => 'https://schema.org/PlasticSurgery',
            'audience' => ['@type' => 'MedicalAudience', 'audienceType' => 'Patient'],
            'lastReviewed' => $t['reviewed'],
            'reviewedBy' => reviewer_ld(site('reviewer')),
            'about' => [
                '@type' => 'MedicalProcedure',
                'name' => $t['name'],
                'procedureType' => 'https://schema.org/SurgicalProcedure',
                'bodyLocation' => $bodyLocations[$t['category']] ?? null,
                'description' => $t['summary'],
                'howPerformed' => implode(' ', $flowTexts),
                'followup' => implode(' ', $t['aftercare']),
            ],
        ],
    ],
];

$sections = [
    'overview' => '施術の概要',
    'for' => 'こんな方に',
    'process' => '施術の流れ',
    'price' => '費用と主なリスク・副作用',
    'sessions' => '回数・経過の目安',
    'downtime' => 'ダウンタイム',
    'contraindications' => '受けられない方',
    'aftercare' => '術後の注意',
    'contact' => 'お問い合わせ・ご予約',
];
$num = array_flip(array_keys($sections));
$heading = static fn (string $id): string => '<span class="c-heading__num" aria-hidden="true">' . e(sc_num($num[$id] + 1)) . '</span>' . e($sections[$id]);

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'kicker' => 'Treatment — ' . $t['category_en'],
      'index' => sc_num($number),
      'title' => $t['name'],
      'lead' => $t['lead'],
      'reviewed' => $t['reviewed'],
      'facts' => [
          ['施術時間', $t['duration']],
          ['麻酔', $t['anesthesia']],
          ['ダウンタイムの目安', $t['downtime_short']],
          ['術後検診', $t['checkups']],
      ],
  ]); ?>

  <div class="l-container p-treatment">
    <nav class="p-treatment__toc" aria-label="ページ内目次">
      <p class="p-treatment__toc-title" lang="en">Contents</p>
      <ol class="p-treatment__toc-list">
        <?php foreach ($sections as $id => $label): ?>
          <li><a href="#<?= e($id) ?>"><span class="p-treatment__toc-num" aria-hidden="true"><?= e(sc_num($num[$id] + 1)) ?></span><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <div class="p-treatment__content">
      <section class="p-treatment__section" id="overview" aria-labelledby="overview-title">
        <h2 class="c-heading" id="overview-title"><?= $heading('overview') ?></h2>
        <p class="p-treatment__summary"><?= e($t['summary']) ?></p>
      </section>

      <section class="p-treatment__section" id="for" aria-labelledby="for-title">
        <h2 class="c-heading" id="for-title"><?= $heading('for') ?></h2>
        <ul class="c-dash-list">
          <?php foreach ($t['for'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
        </ul>
        <p class="c-note">診察の結果、ほかの方法が適していると判断した場合は、その理由とあわせてご提案します。</p>
      </section>

      <section class="p-treatment__section" id="process" aria-labelledby="process-title">
        <h2 class="c-heading" id="process-title"><?= $heading('process') ?></h2>
        <ol class="c-steps">
          <?php foreach ($t['flow'] as $i => $step): [$stepTitle, $stepText] = sc_split_step($step); ?>
            <li class="c-steps__item">
              <span class="c-steps__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span>
              <div class="c-steps__body">
                <?php if ($stepTitle !== ''): ?><h3 class="c-steps__title"><?= e($stepTitle) ?></h3><?php endif; ?>
                <p class="c-steps__text"><?= e($stepText) ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <section class="p-treatment__section" id="price" aria-labelledby="price-title">
        <h2 class="c-heading" id="price-title"><?= $heading('price') ?></h2>
        <p class="p-treatment__intro">費用はすべて税込の総額です。費用とあわせて、起こりうる主なリスク・副作用を必ずご確認ください。</p>
        <div class="p-disclosure" data-disclosure-group>
          <div class="p-disclosure__col p-disclosure__price" data-disclosure="price">
            <h3 class="p-disclosure__title">費用（税込）</h3>
            <table class="c-price-table">
              <caption class="u-visually-hidden"><?= e($t['name']) ?>の費用（税込）</caption>
              <tbody>
                <?php foreach ($t['prices'] as $price): ?>
                  <tr><th scope="row"><?= e($price['label']) ?></th><td><?= e(tax_in($price['amount'])) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <h4 class="p-disclosure__subtitle">料金に含まれるもの</h4>
            <ul class="c-check-list">
              <?php foreach ($t['price_includes'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
            </ul>
            <h4 class="p-disclosure__subtitle">別途かかる費用</h4>
            <table class="c-price-table c-price-table--sub">
              <caption class="u-visually-hidden">別途かかる費用（税込）</caption>
              <tbody>
                <?php foreach ($t['options'] as $option): ?>
                  <tr><th scope="row"><?= e($option['label']) ?></th><td><?= e(tax_in($option['amount'])) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p class="c-note"><?= e($t['price_note']) ?></p>
          </div>
          <div class="p-disclosure__col p-disclosure__risks" data-disclosure="risks">
            <h3 class="p-disclosure__title">主なリスク・副作用</h3>
            <ul class="c-dash-list c-dash-list--risk">
              <?php foreach ($t['risks'] as $risk): ?><li><?= e($risk) ?></li><?php endforeach; ?>
            </ul>
            <p class="c-note">症状の出方や程度には個人差があります。気になる症状があるときは、検診日を待たずにご連絡ください。</p>
          </div>
        </div>
      </section>

      <section class="p-treatment__section" id="sessions" aria-labelledby="sessions-title" data-disclosure="sessions">
        <h2 class="c-heading" id="sessions-title"><?= $heading('sessions') ?></h2>
        <p><?= e($t['sessions']) ?></p>
        <dl class="c-facts">
          <div class="c-facts__item"><dt>施術時間</dt><dd><?= e($t['duration']) ?></dd></div>
          <div class="c-facts__item"><dt>麻酔</dt><dd><?= e($t['anesthesia']) ?></dd></div>
          <div class="c-facts__item"><dt>術後検診</dt><dd><?= e($t['checkups']) ?></dd></div>
        </dl>
      </section>

      <section class="p-treatment__section" id="downtime" aria-labelledby="downtime-title" data-disclosure="downtime">
        <h2 class="c-heading" id="downtime-title"><?= $heading('downtime') ?></h2>
        <p><?= e($t['downtime']) ?></p>
      </section>

      <section class="p-treatment__section" id="contraindications" aria-labelledby="contraindications-title">
        <h2 class="c-heading" id="contraindications-title"><?= $heading('contraindications') ?></h2>
        <p class="p-treatment__intro">次に当てはまる方は、施術を受けられないか、事前にご相談が必要です。</p>
        <ul class="c-dash-list">
          <?php foreach ($t['contraindications'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
        </ul>
        <p class="c-note">未成年の方は、保護者の同意書が必要です。</p>
      </section>

      <section class="p-treatment__section" id="aftercare" aria-labelledby="aftercare-title">
        <h2 class="c-heading" id="aftercare-title"><?= $heading('aftercare') ?></h2>
        <ul class="c-dash-list">
          <?php foreach ($t['aftercare'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
        </ul>
      </section>

      <section class="p-treatment__section p-treatment__contact" id="contact" aria-labelledby="contact-title" data-disclosure="contact">
        <h2 class="c-heading" id="contact-title"><?= $heading('contact') ?></h2>
        <p>施術についてのご質問・カウンセリングのご予約は、Webフォームまたはお電話で承ります。術後の経過で気になる症状がある方は、お電話でご連絡ください。</p>
        <dl class="c-facts c-facts--contact">
          <div class="c-facts__item"><dt>お電話</dt><dd><a class="p-treatment__tel" href="<?= e(sc_tel_href()) ?>"><?= e(site('tel')) ?></a></dd></div>
          <div class="c-facts__item"><dt>受付時間</dt><dd><?= e(site('hours_label')) ?>（休診 <?= e(site('closed_label')) ?>）</dd></div>
          <div class="c-facts__item"><dt>所在地</dt><dd><?= e(site('address.region') . site('address.locality') . site('address.street')) ?></dd></div>
        </dl>
        <a class="c-button" href="<?= e(url('counseling')) ?>">カウンセリングを予約する<span class="c-button__arrow" aria-hidden="true"></span></a>
      </section>

      <aside class="p-treatment__reviewer" aria-label="監修">
        <p class="p-treatment__reviewer-label" lang="en">Reviewed by</p>
        <p class="p-treatment__reviewer-name"><?= e(sc_reviewer_label()) ?></p>
        <p class="p-treatment__reviewer-text">このページの内容は、院長が医学的な観点から確認しています。最終確認日：<?= sc_date($t['reviewed']) ?></p>
        <a class="c-link" href="<?= e(url('doctor')) ?>">医師紹介を見る</a>
      </aside>
    </div>
  </div>

  <section class="p-related l-section" aria-labelledby="related-title">
    <div class="l-container">
      <p class="c-kicker"><span class="c-kicker__label" lang="en">Other treatments</span></p>
      <h2 class="p-related__title" id="related-title">ほかの施術</h2>
      <ul class="p-related__list">
        <?php foreach ($treatments as $i => $other): if ($other['slug'] === $t['slug']) { continue; } ?>
          <li class="p-related__item">
            <a class="p-related__link" href="<?= e(url('treatment', ['slug' => $other['slug']])) ?>">
              <span class="c-numeral p-related__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span>
              <span class="p-related__name"><?= e($other['name']) ?></span>
              <span class="p-related__price"><?= e($other['card_price']['label']) ?> <?= e(sc_price_from($other)) ?></span>
              <span class="p-related__risks">主なリスク：<?= e($other['risks_short']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer'); ?>
