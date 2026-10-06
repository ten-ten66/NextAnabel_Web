<?php
require __DIR__ . '/_init.php';

$fees = site_data('fees');
$doctor = site_data('doctor');
$first = $fees['basic'][0];
$example = find_treatment('ipl');
$examplePrice = $example['prices'][0];

$page = [
    'id' => 'index',
    'description' => (string) site('description'),
    'jsonld' => [
        business_ld([
            'description' => (string) site('description'),
            'alternateName' => site('name_en'),
            'availableService' => array_map(static fn (array $t): array => [
                '@type' => 'MedicalProcedure',
                'name' => $t['name'],
                'url' => absolute_url(url('treatment', ['slug' => $t['slug']])),
            ], treatments()),
        ]),
        [
            '@type' => 'WebSite',
            '@id' => absolute_url('') . '#website',
            'url' => absolute_url(''),
            'name' => site('name'),
            'inLanguage' => 'ja',
            'publisher' => ['@id' => absolute_url('') . '#business'],
        ],
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <section class="p-hero" aria-labelledby="hero-title">
    <div class="p-hero__media" aria-hidden="true">
      <img class="p-hero__image" src="<?= e(asset('img/hero-glaze.webp')) ?>" width="1920" height="1200" alt="" fetchpriority="high">
      <canvas class="p-hero__canvas js-glaze"></canvas>
    </div>
    <div class="l-container p-hero__inner">
      <p class="p-hero__catch"><span class="p-hero__catch-line">納得してから、</span><span class="p-hero__catch-line">はじめる肌治療。</span></p>
      <span class="p-hero__seal" aria-hidden="true">白磁</span>
      <div class="p-hero__intro">
        <p class="p-hero__en" aria-hidden="true"><?= e(site('name_en')) ?></p>
        <h1 class="p-hero__title" id="hero-title"><?= e(site('name')) ?></h1>
        <p class="p-hero__sub">費用の総額・回数の目安・リスクを、<br class="u-br-md">施術の前にすべてご説明します。</p>
        <div class="p-hero__actions">
          <a class="c-button c-button--primary" href="<?= e(url('contact')) ?>">カウンセリングを予約する<?= icon('arrow') ?></a>
          <a class="c-button c-button--ghost" href="<?= e(url('treatments')) ?>">施術と料金を見る</a>
        </div>
        <ul class="p-hero__meta">
          <li>南青山・表参道駅から徒歩4分</li>
          <li>休診日 <?= e(site('closed_label')) ?></li>
        </ul>
      </div>
    </div>
    <button type="button" class="p-hero__toggle js-glaze-toggle" aria-pressed="false" hidden><?= icon('pause') ?><?= icon('play') ?><span class="u-visually-hidden">背景の動きを一時停止</span></button>
  </section>

  <section class="l-section p-home-philosophy" aria-labelledby="philosophy-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Philosophy', 'title' => '当院の考え方', 'id' => 'philosophy-title']); ?>
      </div>
      <div class="l-rail__body">
        <p class="p-home-philosophy__lead">美容皮膚科の治療は、<br class="u-br-lg">受けると決めるまでの時間が、<br class="u-br-lg">いちばん大切だと考えています。</p>
        <p class="p-home-philosophy__text">肌の悩みは、同じように見えても原因が異なります。白磁スキンクリニックでは、皮膚科の診察で原因を見きわめたうえで、治療の選択肢と、それぞれの費用・回数・リスクを並べてご説明します。決めていただくのは、説明を聞き終えたあとで構いません。</p>
        <ul class="p-home-philosophy__list">
          <li class="p-principle">
            <p class="p-principle__en" aria-hidden="true">Explain</p>
            <h3 class="p-principle__title">説明</h3>
            <p class="p-principle__text">見積書には総額を記載し、起こりうる副作用は、頻度の低いものまでお伝えします。施術を受けてみないとわからないことは、わからないと正直に申し上げます。</p>
          </li>
          <li class="p-principle">
            <p class="p-principle__en" aria-hidden="true">Choose</p>
            <h3 class="p-principle__title">選択</h3>
            <p class="p-principle__text">施術をしないという選択肢も含めてご提案します。カウンセリング当日に契約をお願いすることはありません。持ち帰って、ご家族と相談していただけます。</p>
          </li>
          <li class="p-principle">
            <p class="p-principle__en" aria-hidden="true">Follow</p>
            <h3 class="p-principle__title">継続</h3>
            <p class="p-principle__text">施術後の経過は、医師が診察で確認します。肌の反応に合わせて間隔や出力を見直し、気になることがあれば、施術の予定がない日でもご相談いただけます。</p>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="l-section l-section--tint p-home-concerns" aria-labelledby="concerns-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Concerns', 'title' => '悩みから探す', 'id' => 'concerns-title']); ?>
      </div>
      <div class="l-rail__body">
        <p class="p-home-concerns__text">気になる症状を選ぶと、対応する施術を表示します。同じ悩みでも原因によって適した治療は異なるため、最終的な治療法は診察で決めます。</p>
        <?php partial('tx-filter', ['target' => 'home-treatments']); ?>
        <ul class="c-tx-grid" id="home-treatments">
          <?php foreach (treatments() as $t): ?>
            <?php partial('tx-card', ['t' => $t]); ?>
          <?php endforeach; ?>
          <li class="c-tx-more js-filter-anchor">
            <p class="c-tx-more__title">すべての施術と料金</p>
            <p class="c-tx-more__text">施術ごとの回数の目安・ダウンタイム・リスクを一覧で比べられます。</p>
            <ul class="c-tx-more__links">
              <li><a class="c-text-link" href="<?= e(url('treatments')) ?>">施術一覧<?= icon('arrow') ?></a></li>
              <li><a class="c-text-link" href="<?= e(url('price')) ?>">料金表<?= icon('arrow') ?></a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="l-section p-home-pricing" aria-labelledby="pricing-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Pricing', 'title' => '料金の考え方', 'id' => 'pricing-title']); ?>
      </div>
      <div class="l-rail__body p-home-pricing__grid">
        <div class="p-home-pricing__main">
          <p class="p-home-pricing__lead">表示している料金は、<br class="u-br-md">すべて税込の総額です。</p>
          <p class="p-home-pricing__text">施術料には、施術当日の診察や、施術前後の洗顔・冷却・保湿を含みます。初診料や麻酔など、別にかかる費用も料金表にすべて掲載しています。施術の前には見積書をお渡しし、ご了承いただいてから施術を始めます。</p>
          <div class="p-home-pricing__lists">
            <div class="p-home-pricing__list">
              <h3 class="p-home-pricing__heading">施術料に含まれるもの</h3>
              <ul class="c-check-list">
                <?php foreach ($fees['included'] as $item): ?>
                  <li><?= e($item) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="p-home-pricing__list">
              <h3 class="p-home-pricing__heading">別にかかる費用</h3>
              <ul class="c-dash-list">
                <?php foreach ($fees['basic'] as $fee): ?>
                  <li><?= e($fee['label']) ?> <?= price_html($fee['amount']) ?></li>
                <?php endforeach; ?>
                <li>処方薬（必要な場合のみ）</li>
              </ul>
            </div>
          </div>
          <a class="c-text-link" href="<?= e(url('price')) ?>">料金表を見る<?= icon('arrow') ?></a>
        </div>
        <aside class="p-receipt" aria-labelledby="receipt-title">
          <p class="p-receipt__en" aria-hidden="true">Example</p>
          <h3 class="p-receipt__title" id="receipt-title">お会計の例</h3>
          <p class="p-receipt__case">はじめて<?= e($example['name']) ?>（<?= e($examplePrice['label']) ?>）を受ける場合</p>
          <dl class="p-receipt__rows">
            <div class="p-receipt__row">
              <dt><?= e($first['label']) ?></dt>
              <dd><?= price_html($first['amount']) ?></dd>
            </div>
            <div class="p-receipt__row">
              <dt><?= e($example['name']) ?> <?= e($examplePrice['label']) ?></dt>
              <dd><?= price_html($examplePrice['amount']) ?></dd>
            </div>
            <div class="p-receipt__row p-receipt__row--total">
              <dt>お支払いの合計</dt>
              <dd><?= price_html($first['amount'] + $examplePrice['amount']) ?></dd>
            </div>
          </dl>
          <p class="p-receipt__note">麻酔は通常使用しません。処方薬がある場合は、その費用が加わります。2回目からは初診料の代わりに再診料がかかりますが、施術を受ける日は施術料に含みます。</p>
        </aside>
      </div>
    </div>
  </section>

  <section class="l-section l-section--mist p-home-doctor" aria-labelledby="doctor-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Doctor', 'title' => '医師紹介', 'id' => 'doctor-title']); ?>
      </div>
      <div class="l-rail__body p-home-doctor__grid">
        <?php partial('photo', ['src' => 'img/portrait-frame.webp', 'width' => 800, 'height' => 1000, 'caption' => '医師写真（撮影素材に差し替え）', 'class' => 'p-home-doctor__photo']); ?>
        <div class="p-home-doctor__body">
          <p class="p-home-doctor__quote"><?= e($doctor['quote']) ?></p>
          <p class="p-home-doctor__role"><?= e($doctor['role']) ?>／<?= e($doctor['specialty']) ?></p>
          <p class="p-home-doctor__name"><?= e($doctor['name']) ?><span class="p-home-doctor__name-en" aria-hidden="true"><?= e($doctor['name_en']) ?></span></p>
          <p class="p-home-doctor__text"><?= e($doctor['excerpt']) ?></p>
          <a class="c-text-link" href="<?= e(url('doctor')) ?>">院長のプロフィールを見る<?= icon('arrow') ?></a>
        </div>
      </div>
    </div>
  </section>

  <section class="l-section p-home-flow" aria-labelledby="flow-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'First Visit', 'title' => 'はじめての方へ', 'id' => 'flow-title']); ?>
      </div>
      <div class="l-rail__body">
        <p class="p-home-flow__text">ご予約から施術後のケアまでの流れです。初診の日は、問診票のご記入からカウンセリング、診察までで30〜45分ほどかかります。</p>
        <ol class="c-flow">
          <li class="c-flow__step">
            <p class="c-flow__num" aria-hidden="true">01</p>
            <h3 class="c-flow__title">ご予約</h3>
            <p class="c-flow__text">Webフォームは24時間受け付けています。お電話でのご予約は、診療時間内にお願いします。</p>
          </li>
          <li class="c-flow__step">
            <p class="c-flow__num" aria-hidden="true">02</p>
            <h3 class="c-flow__title">カウンセリング</h3>
            <p class="c-flow__text">気になる症状や、これまでの治療歴を伺います。施術ごとの費用・回数・リスクをご説明します。</p>
          </li>
          <li class="c-flow__step">
            <p class="c-flow__num" aria-hidden="true">03</p>
            <h3 class="c-flow__title">診察</h3>
            <p class="c-flow__text">医師が肌の状態を診察し、施術を受けられるかどうかを判断します。見積書をお渡しします。</p>
          </li>
          <li class="c-flow__step">
            <p class="c-flow__num" aria-hidden="true">04</p>
            <h3 class="c-flow__title">施術</h3>
            <p class="c-flow__text">ご納得いただけた場合にのみ、施術を行います。同じ日に受けるか、別の日に予約するかを選べます。</p>
          </li>
          <li class="c-flow__step">
            <p class="c-flow__num" aria-hidden="true">05</p>
            <h3 class="c-flow__title">アフターケア</h3>
            <p class="c-flow__text">施術後の注意点を書面でお渡しします。経過は再診で確認し、気になる症状があればご連絡ください。</p>
          </li>
        </ol>
        <a class="c-text-link" href="<?= e(url('faq')) ?>">よくあるご質問を見る<?= icon('arrow') ?></a>
      </div>
    </div>
  </section>

  <section class="l-section l-section--paper p-home-news" aria-labelledby="news-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'News', 'title' => 'お知らせ', 'id' => 'news-title']); ?>
      </div>
      <div class="l-rail__body">
        <ul class="c-news">
          <?php foreach (site_data('news') as $item): ?>
            <li class="c-news__item">
              <?= time_tag($item['date']) ?>
              <span class="c-news__category"><?= e($item['category']) ?></span>
              <p class="c-news__title"><?= e($item['title']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>

  <section class="l-section l-section--tint p-home-access" aria-labelledby="access-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Hours & Access', 'title' => '診療時間・アクセス', 'id' => 'access-title']); ?>
      </div>
      <div class="l-rail__body p-home-access__grid">
        <div class="p-home-access__hours">
          <?php partial('reception'); ?>
          <?php partial('hours-table'); ?>
        </div>
        <div class="p-home-access__place">
          <div class="p-home-access__map">
            <?php partial('access-map', ['prefix' => 'home-map']); ?>
          </div>
          <address class="p-home-access__address">
            〒<?= e(site('address.postal_code')) ?> <?= e(full_address()) ?>
          </address>
          <ul class="c-dash-list p-home-access__routes">
            <?php foreach ((array) site('access') as $route): ?>
              <li><?= e($route) ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="p-home-access__links">
            <a class="c-text-link" href="<?= e(url('clinic#access')) ?>">アクセスの詳細<?= icon('arrow') ?></a>
            <a class="c-text-link" href="<?= e(map_url()) ?>" target="_blank" rel="noopener">地図アプリで開く<?= icon('external') ?><span class="u-visually-hidden">（新しいタブで開きます）</span></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
