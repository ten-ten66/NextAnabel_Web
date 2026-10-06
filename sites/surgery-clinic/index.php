<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'index',
    'description' => site('description'),
    'jsonld' => [business_ld()],
];

$principles = [
    [
        'title' => ['カウンセリングで、', '適応を見極める。'],
        'text' => 'ご希望の仕上がりを伺ったうえで、まぶたの厚みや皮膚のたるみ、骨格、今後のご予定まで医師が診察し、その方法が本当に合っているかを判断します。ご希望の施術であっても、適さないと判断した場合はおすすめしません。',
    ],
    [
        'title' => ['できないことも、', '伝える。'],
        'text' => '手術で変えられること・変えられないこと、起こりうるリスクや副作用、麻酔やお薬を含めた費用の総額を、ご契約の前にすべてご説明します。考える時間を持っていただくため、カウンセリング当日に手術は行いません。',
    ],
    [
        'title' => ['術後まで、', '伴走する。'],
        'text' => '腫れや内出血が落ち着くまでの経過、決められた時期の検診、気になる症状が出たときの対応まで、執刀した医師が責任をもって診察します。手術を受けられた方には、夜間・休診日の連絡先をお渡ししています。',
    ],
];

$flow = [
    ['en' => 'Reservation', 'title' => 'ご予約', 'text' => 'Webフォームまたはお電話で、カウンセリングの日時をご予約ください。ご相談だけのご来院も承っています。', 'meta' => 'Webフォーム・お電話'],
    ['en' => 'Counseling', 'title' => 'カウンセリング', 'text' => '医師がご希望を伺い、施術の適応、リスクと副作用、費用の総額をご説明します。当日に手術を行うことはありません。', 'meta' => '所要時間 約60分'],
    ['en' => 'Design', 'title' => '診察・デザイン', 'text' => '手術を決められた方は、術前の診察で仕上がりのデザインを医師と最終確認し、同意書の内容をご説明します。必要に応じて血液検査を行います。', 'meta' => '手術日より前に実施'],
    ['en' => 'Surgery', 'title' => '施術当日', 'text' => '麻酔・施術のあと、状態が落ち着くまで院内でお休みいただきます。ご帰宅前に、注意事項と緊急時の連絡先をお渡しします。', 'meta' => '所要時間は施術により異なります'],
    ['en' => 'Aftercare', 'title' => '術後検診', 'text' => '施術内容に応じた時期に検診を行い、腫れや傷あとの経過を確認します。所定の検診費用は、施術料金に含まれています。', 'meta' => '気になる症状は随時ご相談ください'],
];

$days = ['月', '火', '水', '木', '金', '土'];
$doctor = require __DIR__ . '/data/doctor.php';

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <section class="p-hero" aria-labelledby="hero-title" data-hero>
    <div class="p-hero__media" aria-hidden="true">
      <img class="p-hero__image" src="<?= e(asset('img/hero-silk.webp')) ?>" width="1920" height="1200" alt="" fetchpriority="high" decoding="async" data-hero-image>
    </div>
    <p class="p-hero__vertical" aria-hidden="true" lang="en">Aesthetic Surgery — Ginza, Tokyo</p>
    <div class="l-container p-hero__inner">
      <p class="c-kicker p-hero__kicker"><span class="c-kicker__label" lang="en"><?= e(site('name_en')) ?></span></p>
      <h1 class="p-hero__title" id="hero-title" data-split-intro data-split-mask><span class="p-hero__line">オルヴァン</span><span class="p-hero__line p-hero__line--indent">美容外科</span></h1>
      <div class="p-hero__body">
        <p class="p-hero__catch"><span class="u-ib">できることと、</span><span class="u-ib">できないことを、</span><span class="u-ib">最初にお伝えします。</span></p>
        <p class="p-hero__text">形成外科専門医の院長が、カウンセリングから手術、術後の検診までを一貫して担当する、銀座の美容外科です。</p>
        <div class="p-hero__actions">
          <a class="c-button" href="<?= e(url('counseling')) ?>">カウンセリングを予約する<span class="c-button__arrow" aria-hidden="true"></span></a>
          <a class="c-link" href="#treatments">施術メニューを見る</a>
        </div>
      </div>
      <span class="p-hero__rule" aria-hidden="true" data-hero-rule></span>
      <dl class="p-hero__info">
        <div class="p-hero__info-item">
          <dt>診療時間</dt>
          <dd>月〜土 <span class="c-digits">10:00–19:00</span><span class="p-hero__info-sub">休診 <?= e(site('closed_label')) ?></span></dd>
        </div>
        <div class="p-hero__info-item">
          <dt>アクセス</dt>
          <dd><?= e(site('access.0')) ?></dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="p-philosophy l-section" id="philosophy" aria-labelledby="philosophy-title">
    <div class="l-container">
      <header class="c-section-head c-section-head--split">
        <p class="c-kicker"><span class="c-kicker__index">01</span><span class="c-kicker__label" lang="en">Philosophy</span></p>
        <h2 class="c-section-head__title" id="philosophy-title"><span class="u-ib">すべてを知ってから、</span><span class="u-ib">決めていただく。</span></h2>
        <p class="c-section-head__lead">美容外科の手術には、一度受けると元に戻すことが難しいものも少なくありません。だからこそ私たちは、手術そのものと同じくらい、手術の前と後の時間を大切にしています。</p>
      </header>
      <ol class="p-philosophy__list">
        <?php foreach ($principles as $i => $item): ?>
          <li class="p-philosophy__item">
            <span class="c-numeral p-philosophy__num" aria-hidden="true" data-drift><?= e(sc_num($i + 1)) ?></span>
            <h3 class="p-philosophy__title"><?php foreach ($item['title'] as $part): ?><span class="u-ib"><?= e($part) ?></span><?php endforeach; ?></h3>
            <p class="p-philosophy__text"><?= e($item['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <div class="p-interlude">
    <div class="p-interlude__media" aria-hidden="true">
      <img src="<?= e(asset('img/silk-detail.webp')) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async" data-parallax>
    </div>
    <div class="l-container p-interlude__inner">
      <p class="p-interlude__quote"><span class="u-ib">手術の日は、</span><span class="u-ib">ゴールではなく、</span><span class="u-ib">経過のはじまり。</span></p>
      <p class="p-interlude__note">所定の術後検診の費用は、すべて施術料金に含まれています。</p>
    </div>
  </div>

  <section class="p-menu l-section" id="treatments" aria-labelledby="treatments-title">
    <div class="l-container p-menu__layout">
      <header class="c-section-head p-menu__head">
        <p class="c-kicker"><span class="c-kicker__index">02</span><span class="c-kicker__label" lang="en">Treatments</span></p>
        <h2 class="c-section-head__title" id="treatments-title">施術メニュー</h2>
        <p class="c-section-head__lead">表示している費用は、すべて税込の総額です。麻酔・処方薬・術後検診など、料金に含まれるものは各施術のページに明記しています。</p>
        <p class="p-menu__note">カウンセリング料（<?= e(tax_in((int) site('counseling_fee'))) ?>）は別途かかります。</p>
      </header>
      <ul class="p-menu__list">
        <?php foreach (sc_treatments() as $i => $t): ?>
          <li class="p-menu__item">
            <article class="c-card" aria-labelledby="menu-<?= e($t['slug']) ?>">
              <div class="c-card__top">
                <span class="c-numeral c-card__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span>
                <p class="c-card__category"><span class="c-card__category-en" lang="en"><?= e($t['category_en']) ?></span><?= e($t['category_label']) ?></p>
              </div>
              <h3 class="c-card__title" id="menu-<?= e($t['slug']) ?>"><a class="c-card__link" href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"><?= e($t['name']) ?></a></h3>
              <p class="c-card__lead"><?= e($t['lead']) ?></p>
              <dl class="c-card__facts" data-disclosure-group>
                <div class="c-card__fact" data-disclosure="price">
                  <dt>費用</dt>
                  <dd><span class="c-card__price-label"><?= e($t['card_price']['label']) ?></span><span class="c-card__price"><?= e(sc_price_from($t)) ?></span></dd>
                </div>
                <div class="c-card__fact" data-disclosure="downtime">
                  <dt>ダウンタイム</dt>
                  <dd><?= e($t['downtime_short']) ?></dd>
                </div>
                <div class="c-card__fact" data-disclosure="risks">
                  <dt>主なリスク</dt>
                  <dd><?= e($t['risks_short']) ?></dd>
                </div>
              </dl>
              <p class="c-card__more" aria-hidden="true">詳しく見る<span class="c-card__arrow"></span></p>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="p-flow" id="flow" aria-labelledby="flow-title" data-flow>
    <div class="p-flow__pin" data-flow-pin>
      <div class="l-container p-flow__head">
        <div class="c-section-head">
          <p class="c-kicker"><span class="c-kicker__index">03</span><span class="c-kicker__label" lang="en">Flow</span></p>
          <h2 class="c-section-head__title" id="flow-title"><span class="u-ib">ご予約から、</span><span class="u-ib">術後の検診まで</span></h2>
        </div>
        <p class="p-flow__lead">手術を受けるかどうかは、カウンセリングのあとでゆっくりご検討ください。手術日を決めるのは、すべての説明にご納得いただいてからです。</p>
        <div class="p-flow__progress" aria-hidden="true"><span class="p-flow__progress-bar" data-flow-progress></span></div>
      </div>
      <div class="p-flow__viewport" data-flow-viewport>
        <ol class="p-flow__list" data-flow-track>
          <?php foreach ($flow as $i => $step): ?>
            <li class="p-flow__step">
              <span class="c-numeral p-flow__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span>
              <div class="p-flow__body">
                <p class="p-flow__en" lang="en"><?= e($step['en']) ?></p>
                <h3 class="p-flow__title"><?= e($step['title']) ?></h3>
                <p class="p-flow__text"><?= e($step['text']) ?></p>
                <p class="p-flow__meta"><?= e($step['meta']) ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>

  <section class="p-doctor l-section" id="doctor" aria-labelledby="doctor-title">
    <div class="l-container p-doctor__layout">
      <?php partial('frame', ['src' => 'img/portrait-frame.webp', 'width' => 800, 'height' => 1000, 'shape' => 'portrait', 'caption' => '医師写真（撮影素材に差し替え）', 'class' => 'p-doctor__photo']); ?>
      <div class="p-doctor__body">
        <div class="c-section-head">
          <p class="c-kicker"><span class="c-kicker__index">04</span><span class="c-kicker__label" lang="en">Doctor</span></p>
          <h2 class="c-section-head__title" id="doctor-title"><span class="u-ib">執刀する医師が、</span><span class="u-ib">最初にお話を伺います。</span></h2>
        </div>
        <p class="p-doctor__name">
          <span class="p-doctor__role"><?= e($doctor['role']) ?></span>
          <span class="p-doctor__fullname"><?= e($doctor['name']) ?></span>
          <span class="p-doctor__kana"><?= e($doctor['kana']) ?></span>
        </p>
        <p class="p-doctor__license"><?= e($doctor['license']) ?></p>
        <p class="p-doctor__text"><?= e($doctor['excerpt']) ?></p>
        <ul class="p-doctor__credentials">
          <?php foreach (array_slice($doctor['credentials'], 0, 2) as $credential): ?>
            <li><?= e($credential) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="c-link" href="<?= e(url('doctor')) ?>">医師紹介を見る</a>
      </div>
    </div>
  </section>

  <section class="p-cases-teaser l-section" id="cases" aria-labelledby="cases-title">
    <div class="l-container p-cases-teaser__layout">
      <div class="p-cases-teaser__body">
        <div class="c-section-head">
          <p class="c-kicker"><span class="c-kicker__index">05</span><span class="c-kicker__label" lang="en">Cases</span></p>
          <h2 class="c-section-head__title" id="cases-title">症例写真の掲載について</h2>
        </div>
        <p class="p-cases-teaser__text">当院では、掲載の同意をいただいた患者様の写真のみを掲載しています。写真の加工・修整は行わず、治療内容・費用・主なリスクや副作用を、写真のすぐ隣に記載します。</p>
        <a class="c-link" href="<?= e(url('cases')) ?>">掲載方針と症例写真を見る</a>
      </div>
      <div class="p-cases-teaser__diagram" aria-hidden="true">
        <div class="p-cases-teaser__frames">
          <span class="p-cases-teaser__frame">術前</span>
          <span class="p-cases-teaser__frame">術後</span>
        </div>
        <ul class="p-cases-teaser__labels">
          <li>治療内容</li>
          <li>費用（税込）</li>
          <li>主なリスク・副作用</li>
          <li>経過期間・担当医</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="p-access l-section" id="access" aria-labelledby="access-title">
    <div class="l-container">
      <div class="c-section-head">
        <p class="c-kicker"><span class="c-kicker__index">06</span><span class="c-kicker__label" lang="en">Access</span></p>
        <h2 class="c-section-head__title" id="access-title">診療時間・アクセス</h2>
      </div>
      <div class="p-access__grid">
        <div class="p-access__hours">
          <table class="c-hours">
            <caption class="c-hours__caption">診療時間（完全予約制）</caption>
            <thead>
              <tr>
                <th scope="col"><span class="u-visually-hidden">時間帯</span></th>
                <?php foreach ($days as $day): ?><th scope="col"><?= e($day) ?></th><?php endforeach; ?>
                <th scope="col">日・祝</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row" class="c-digits">10:00–19:00</th>
                <?php foreach ($days as $day): ?><td><span class="c-hours__mark" aria-hidden="true"></span><span class="u-visually-hidden">診療</span></td><?php endforeach; ?>
                <td><span class="c-hours__closed" aria-hidden="true">—</span><span class="u-visually-hidden">休診</span></td>
              </tr>
            </tbody>
          </table>
          <ul class="c-notes p-access__notes">
            <li>最終受付は18:00です（カウンセリングの所要時間は約<?= e(site('counseling_minutes')) ?>分）。</li>
            <li>手術の開始時間は、施術内容に合わせて個別にご案内します。</li>
            <li>休診日：<?= e(site('closed_label')) ?></li>
          </ul>
        </div>
        <dl class="p-access__info">
          <div class="p-access__row">
            <dt>所在地</dt>
            <dd><address class="p-access__address">〒<?= e(site('address.postal_code')) ?><br><?= e(site('address.region') . site('address.locality') . site('address.street')) ?></address></dd>
          </div>
          <div class="p-access__row">
            <dt>最寄り駅</dt>
            <dd>
              <ul class="p-access__stations">
                <?php foreach (site('access') as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
              </ul>
            </dd>
          </div>
          <div class="p-access__row">
            <dt>お電話</dt>
            <dd><a class="p-access__tel" href="<?= e(sc_tel_href()) ?>"><?= e(site('tel')) ?></a></dd>
          </div>
          <div class="p-access__row">
            <dt>地図</dt>
            <dd><a class="c-link c-link--external" href="<?= e(site('map_url')) ?>" target="_blank" rel="noopener">地図アプリで開く<span class="u-visually-hidden">（新しいタブで開きます）</span></a></dd>
          </div>
        </dl>
        <figure class="p-access__map">
          <svg class="c-map" viewBox="0 0 640 420" role="img" aria-labelledby="map-title">
            <title id="map-title">周辺地図のイラスト。東京メトロ銀座駅A2出口から徒歩約5分、銀座一丁目駅から徒歩約6分。</title>
            <rect class="c-map__bg" width="640" height="420"/>
            <g class="c-map__streets">
              <path d="M0 96H640M0 196H640M0 300H640M0 372H640M92 0V420M214 0V420M436 0V420M560 0V420"/>
            </g>
            <path class="c-map__avenue" d="M330 0V420"/>
            <path class="c-map__avenue c-map__avenue--cross" d="M0 248H640"/>
            <text class="c-map__street-label" x="342" y="30">中央通り</text>
            <text class="c-map__street-label" x="18" y="238">晴海通り</text>
            <path class="c-map__route" d="M300 276V318H408V340"/>
            <path class="c-map__route" d="M470 74V168H408V310"/>
            <g class="c-map__station">
              <rect x="268" y="262" width="28" height="28"/>
              <text x="250" y="312" text-anchor="end">銀座駅 A2出口</text>
            </g>
            <g class="c-map__station">
              <rect x="456" y="48" width="28" height="28"/>
              <text x="494" y="68">銀座一丁目駅</text>
            </g>
            <g class="c-map__clinic">
              <circle cx="408" cy="352" r="9"/>
              <circle class="c-map__clinic-ring" cx="408" cy="352" r="20"/>
              <text class="c-map__clinic-name" x="438" y="357">オルヴァン美容外科</text>
              <text class="c-map__clinic-sub" x="438" y="381">サンプルタワー8階</text>
              <text class="c-map__clinic-short" x="440" y="361">当院</text>
            </g>
          </svg>
          <figcaption class="c-frame__caption">周辺地図（イラスト）。正確な位置は地図アプリでご確認ください。</figcaption>
        </figure>
        <?php partial('frame', ['src' => 'img/interior.webp', 'width' => 1200, 'height' => 800, 'shape' => 'landscape', 'caption' => '院内写真（撮影素材に差し替え）', 'class' => 'p-access__photo']); ?>
      </div>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer'); ?>
