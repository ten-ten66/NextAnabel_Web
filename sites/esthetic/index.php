<?php
require __DIR__ . '/_init.php';

$menu = require __DIR__ . '/data/menu.php';
$seasons = require __DIR__ . '/data/seasons.php';
$faq = require __DIR__ . '/data/faq.php';
$staff = require __DIR__ . '/data/staff.php';
$season = current_season($seasons, new DateTimeImmutable('now'));

$flow = [
    ['title' => 'カウンセリング', 'time' => '約15分', 'text' => 'お茶をお出しして、今日の体調や、気になっているところを伺います。その日の香りも、ここで一緒に選びます。'],
    ['title' => 'お着替え', 'time' => '約5分', 'text' => '個室でガウンにお着替えください。髪をまとめるヘアバンドや、メイク落としも用意しています。'],
    ['title' => 'トリートメント', 'time' => '45〜120分', 'text' => '力加減を確かめながら、ゆっくり進めます。途中で眠ってしまっても大丈夫です。'],
    ['title' => 'お茶とアフターカウンセリング', 'time' => '約15分', 'text' => '季節のお茶を飲みながら、今日の様子をお話しします。おうちでの過ごし方も、ご希望があればお伝えします。'],
];

$pillars = [
    ['icon' => 'i-season', 'en' => 'Season', 'title' => '季節の香り', 'text' => '二十四節気に合わせて、オイルとお茶の香りを少しずつ替えています。春はよもぎ、梅雨は檜、秋は金木犀。香りで季節の移ろいを感じていただけたらうれしいです。'],
    ['icon' => 'i-hands', 'en' => 'Hands', 'title' => '手のぬくもり', 'text' => '機械は使わず、手のひらの温度でほぐしていきます。力加減は、施術中もいつでもお声がけください。強さよりも、心地よさを大切にしています。'],
    ['icon' => 'i-quiet', 'en' => 'Quiet', 'title' => '静かな時間', 'text' => 'ご予約は一枠ずつ、ほかのお客さまと重ならないように。施術のあとは、お茶を飲みながらゆっくり身支度をしていただけます。'],
];

$gallery = [
    ['img' => 'hero-leaves.webp', 'w' => 1920, 'h' => 1200, 'shape' => 'wide', 'title' => '窓辺の葉影', 'text' => '午後になると、麻のカーテンに葉の影が落ちます。'],
    ['img' => 'room.webp', 'w' => 1200, 'h' => 800, 'shape' => 'tall', 'title' => '施術室', 'text' => 'ベッドのまわりは、生成りと苔色でまとめました。'],
    ['img' => 'stones.webp', 'w' => 1200, 'h' => 900, 'shape' => 'wide', 'title' => '温石', 'text' => 'ボディトリートメントで使う、平たい石です。'],
    ['img' => 'leaves-mauve.webp', 'w' => 1200, 'h' => 900, 'shape' => 'tall', 'title' => '藤色の個室', 'text' => '奥の個室は、藤色のリネンで。'],
    ['img' => 'leaves-room.webp', 'w' => 1200, 'h' => 900, 'shape' => 'wide', 'title' => '待合', 'text' => 'お茶をお出しする、小さな待合です。'],
];

$categoryIcons = ['facial' => 'i-facial', 'body' => 'i-body', 'headspa' => 'i-head'];

$page = [
    'id' => 'index',
    'description' => site('description'),
    'jsonld' => [business_ld()],
    'vendor' => ['gsap'],
    'has_reserve' => true,
    'bodyClass' => 'is-home',
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">

  <section class="p-hero js-hero" aria-labelledby="hero-title">
    <div class="p-hero__visual" aria-hidden="true">
      <div class="p-hero__layer p-hero__layer--far">
        <div class="p-hero__layer-inner js-hero-depth" data-depth="5">
          <img class="p-hero__far-img js-hero-far" src="<?= e(asset('img/hero-leaves.webp')) ?>" width="1920" height="1200" alt="" fetchpriority="high" decoding="async">
        </div>
      </div>
      <div class="p-hero__layer p-hero__layer--shade">
        <div class="p-hero__layer-inner js-hero-depth" data-depth="12">
          <?php partial('hero-branch', ['mode' => 'shade']); ?>
        </div>
      </div>
      <div class="p-hero__layer p-hero__layer--line">
        <div class="p-hero__layer-inner js-hero-depth" data-depth="22">
          <?php partial('hero-branch', ['mode' => 'line']); ?>
        </div>
      </div>
    </div>

    <div class="p-hero__inner l-container">
      <div class="p-hero__head">
        <p class="p-hero__eyebrow" data-intro="text" style="--intro-delay: .4s">自由が丘のフェイシャル・<br class="u-br-sp">ボディトリートメントサロン</p>
        <h1 class="p-hero__title" id="hero-title">
          <span class="p-hero__name"><span class="p-hero__char" data-intro="text" style="--intro-delay: .05s">苔</span><span class="p-hero__char" data-intro="text" style="--intro-delay: .13s">と</span><span class="p-hero__char" data-intro="text" style="--intro-delay: .21s">麻</span></span>
          <span class="p-hero__name-en" lang="en" data-intro="text" style="--intro-delay: .3s">koke to asa</span>
        </h1>
        <svg class="p-hero__swash" viewBox="0 0 240 18" aria-hidden="true" focusable="false" data-intro="draw"><path class="js-hero-swash" d="M3 12c26-9 48 3 76-3s52-9 78-3 44 5 80-4"/></svg>
      </div>

      <p class="p-hero__catch"><span class="p-hero__catch-line" data-intro="text" style="--intro-delay: .25s">静けさを、</span><span class="p-hero__catch-line" data-intro="text" style="--intro-delay: .37s">手のひらから。</span></p>

      <div class="p-hero__body">
        <p class="p-hero__lead" data-intro="text" style="--intro-delay: .47s">自由が丘の路地にある、個室ふたつの小さなサロンです。季節の植物の香りと手のひらのぬくもりで、呼吸がゆっくり深くなる時間をお届けします。</p>
        <div class="p-hero__actions" data-intro="text" style="--intro-delay: .54s">
          <span class="c-magnet js-magnet">
            <a class="c-button c-button--primary c-button--lg" href="#reserve"><span class="c-button__label">ご予約・空き状況</span><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a>
          </span>
          <a class="c-button c-button--ghost c-button--lg" href="<?= e(url('menu')) ?>">メニューと料金</a>
        </div>
      </div>

      <dl class="p-hero__info" data-intro="text" style="--intro-delay: .61s">
        <div class="p-hero__info-row">
          <dt>営業時間</dt>
          <dd><?= e(site('hours_label')) ?><span class="u-nowrap">（最終受付 <?= e(site('last_entry')) ?>）</span></dd>
        </div>
        <div class="p-hero__info-row">
          <dt>定休日</dt>
          <dd><?= e(site('closed_label')) ?></dd>
        </div>
        <div class="p-hero__info-row">
          <dt>アクセス</dt>
          <dd>自由が丘駅 正面口から徒歩6分</dd>
        </div>
        <div class="p-hero__info-row p-hero__info-row--season">
          <dt>いまの香り</dt>
          <dd><span class="js-season-scent"><?= e($season['scent']) ?></span>（<span class="js-season-sekki"><?= e($season['sekki']) ?></span>）</dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="p-about l-section" id="about" aria-labelledby="about-title">
    <div class="l-container">
      <div class="p-about__grid">
        <div class="p-about__head">
          <?php partial('section-head', ['en' => 'About', 'title' => 'はじめに', 'id' => 'about-title']); ?>
          <p class="p-about__statement">葉の影がゆれる部屋で、<br>ゆっくり、息をする。</p>
        </div>
        <div class="p-about__text">
          <p>苔と麻は、個室がふたつだけの小さなサロンです。窓から入るやわらかな光と、洗いざらしの麻のリネン、季節の植物の香り。ここでは、いつもより少しゆっくり話して、ゆっくり呼吸をしてください。</p>
          <p>お話ししたい日も、黙っていたい日も。過ごし方はお好みで、どうぞ。</p>
        </div>
        <div class="p-about__media c-parallax" aria-hidden="true">
          <img src="<?= e(asset('img/leaves-room.webp')) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async" data-parallax>
        </div>
      </div>
      <ul class="p-about__pillars">
        <?php foreach ($pillars as $pillar): ?>
          <li class="p-pillar">
            <svg class="p-pillar__icon" aria-hidden="true" focusable="false"><use href="#<?= e($pillar['icon']) ?>"/></svg>
            <p class="p-pillar__en" lang="en" aria-hidden="true"><?= e($pillar['en']) ?></p>
            <h3 class="p-pillar__title"><?= e($pillar['title']) ?></h3>
            <p class="p-pillar__text"><?= e($pillar['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="p-season js-marquee" aria-labelledby="season-title">
    <div class="l-container p-season__head">
      <h2 class="p-season__title" id="season-title"><span class="p-season__en" lang="en" aria-hidden="true">Season</span>香りのこよみ</h2>
      <p class="p-season__lead">二十四節気に合わせて、オイルとお茶の香りを替えています。</p>
      <button type="button" class="p-season__toggle js-marquee-toggle">
        <svg class="c-icon js-marquee-icon" aria-hidden="true" focusable="false"><use href="#i-pause"/></svg>
        <span class="js-marquee-label">動きを止める</span>
      </button>
    </div>
    <div class="p-season__viewport">
      <ul class="p-season__track js-marquee-track">
        <?php foreach ($seasons as $item): ?>
          <?php $isNow = $item['sekki'] === $season['sekki']; ?>
          <li class="p-season__item<?= $isNow ? ' is-current' : '' ?>" data-sekki="<?= e($item['sekki']) ?>">
            <span class="p-season__sekki"><?= e($item['sekki']) ?></span>
            <span class="p-season__scent"><?= e($item['scent']) ?></span>
            <span class="p-season__date"><?= e((int) substr($item['start'], 0, 2)) ?>月<?= e((int) substr($item['start'], 3, 2)) ?>日頃から</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="p-menu l-section" id="menu" aria-labelledby="menu-title">
    <div class="l-container">
      <?php partial('section-head', ['en' => 'Menu', 'title' => 'メニュー', 'id' => 'menu-title', 'lead' => 'はじめての方には、60分前後のメニューをおすすめしています。料金はすべて税込です。']); ?>
      <ul class="p-menu__list">
        <?php foreach ($menu['categories'] as $i => $category): ?>
          <?php
          $featured = $category['items'][0];
          foreach ($category['items'] as $item) {
              if (!empty($item['featured'])) {
                  $featured = $item;
                  break;
              }
          }
          ?>
          <li class="p-menu-card p-menu-card--<?= e($i + 1) ?>">
            <div class="p-menu-card__media c-parallax" aria-hidden="true">
              <img src="<?= e(asset('img/' . $category['image'])) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async" data-parallax>
            </div>
            <div class="p-menu-card__body">
              <p class="p-menu-card__cat">
                <svg class="p-menu-card__icon" aria-hidden="true" focusable="false"><use href="#<?= e($categoryIcons[$category['id']] ?? 'i-leaf') ?>"/></svg>
                <span class="p-menu-card__cat-ja"><?= e($category['label']) ?></span>
                <span class="p-menu-card__cat-en" lang="en" aria-hidden="true"><?= e($category['en']) ?></span>
              </p>
              <h3 class="p-menu-card__title"><?= e($featured['name']) ?></h3>
              <p class="p-menu-card__text"><?= e($featured['summary']) ?></p>
              <dl class="p-menu-card__prices">
                <?php foreach ($featured['plans'] as $plan): ?>
                  <div class="p-menu-card__price">
                    <dt><?= e(minutes($plan['minutes'])) ?><?php if (!empty($plan['note'])): ?><small><?= e($plan['note']) ?></small><?php endif; ?></dt>
                    <dd><?= e(tax_in($plan['amount'])) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
              <p class="p-menu-card__more"><a class="c-link-arrow" href="<?= e(url('menu#' . $category['id'])) ?>"><?= e($category['label']) ?>のメニューを見る<svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="p-menu__all">
        <a class="c-button c-button--ghost" href="<?= e(url('menu')) ?>">すべてのメニューと料金<svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a>
      </p>
    </div>
  </section>

  <section class="p-flow l-section" id="flow" aria-labelledby="flow-title">
    <div class="l-container p-flow__grid">
      <div class="p-flow__aside">
        <?php partial('section-head', ['en' => 'Flow', 'title' => '施術の流れ', 'id' => 'flow-title', 'lead' => '60分のメニューなら、お着替えとお茶の時間を含めて、全体でおよそ1時間半を見ておいてください。']); ?>
        <div class="p-flow__media c-parallax" aria-hidden="true">
          <img src="<?= e(asset('img/stones.webp')) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async" data-parallax>
        </div>
      </div>
      <div class="p-flow__body js-vine">
        <svg class="p-flow__vine js-vine-svg" viewBox="0 0 64 1000" preserveAspectRatio="none" aria-hidden="true" focusable="false">
          <path class="p-flow__stem js-vine-stem" d="M32 0C32 60 22 90 22 125S32 190 32 250 42 310 42 375 32 440 32 500 22 560 22 625 32 690 32 750 42 810 42 875 32 940 32 1000" vector-effect="non-scaling-stroke"/>
          <g class="js-vine-leaves"></g>
        </svg>
        <ol class="p-flow__list">
          <?php foreach ($flow as $i => $step): ?>
            <li class="p-flow__step">
              <span class="p-flow__node js-vine-node" aria-hidden="true"><?= e(sprintf('%02d', $i + 1)) ?></span>
              <div class="p-flow__content">
                <h3 class="p-flow__title"><?= e($step['title']) ?></h3>
                <p class="p-flow__time"><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-clock"/></svg><?= e($step['time']) ?></p>
                <p class="p-flow__text"><?= e($step['text']) ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>

  <section class="p-space l-section" id="space" aria-labelledby="space-title">
    <div class="l-container">
      <?php partial('section-head', ['en' => 'Space', 'title' => '空間', 'id' => 'space-title', 'lead' => '個室はふたつ。窓辺には、季節の枝ものを飾っています。']); ?>
    </div>
    <section class="p-space__carousel js-gallery" aria-roledescription="カルーセル" aria-label="店内の様子" data-swiper-js="<?= e(asset('vendor/swiper-bundle.min.js')) ?>" data-swiper-css="<?= e(asset('vendor/swiper-bundle.min.css')) ?>">
      <div class="swiper p-space__swiper js-gallery-swiper">
        <div class="swiper-wrapper p-space__wrapper js-gallery-wrapper" tabindex="0">
          <?php foreach ($gallery as $i => $slide): ?>
            <div class="swiper-slide p-space__slide p-space__slide--<?= e($slide['shape']) ?>" data-title="<?= e($slide['title']) ?>">
              <figure class="p-space__figure">
                <div class="p-space__media">
                  <img src="<?= e(asset('img/' . $slide['img'])) ?>" width="<?= e($slide['w']) ?>" height="<?= e($slide['h']) ?>" alt="" loading="lazy" decoding="async" data-swiper-parallax-x="14%">
                  <span class="p-space__tag">店内写真（撮影素材に差し替え）</span>
                </div>
                <figcaption class="p-space__caption">
                  <span class="p-space__no" lang="en" aria-hidden="true"><?= e(sprintf('%02d', $i + 1)) ?></span>
                  <span class="p-space__name"><?= e($slide['title']) ?></span>
                  <span class="p-space__text"><?= e($slide['text']) ?></span>
                </figcaption>
              </figure>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="p-space__controls js-gallery-controls" hidden>
        <button type="button" class="c-round-button js-gallery-prev" aria-label="前の写真">
          <svg class="c-icon c-icon--flip" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg>
        </button>
        <button type="button" class="c-round-button js-gallery-next" aria-label="次の写真">
          <svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg>
        </button>
        <div class="p-space__pagination js-gallery-pagination"></div>
        <p class="p-space__count" aria-hidden="true"><span class="js-gallery-current">01</span> / <?= e(sprintf('%02d', count($gallery))) ?></p>
        <p class="u-visually-hidden js-gallery-status" aria-live="polite"></p>
      </div>
      <p class="p-space__hint js-gallery-hint">横にスクロールして、ほかの写真をご覧いただけます。</p>
    </section>
  </section>

  <section class="p-staff l-section" id="staff" aria-labelledby="staff-title">
    <div class="l-container">
      <?php partial('section-head', ['en' => 'Therapists', 'title' => 'セラピスト', 'id' => 'staff-title', 'lead' => 'ふたりで営んでいます。どちらが担当しても、カウンセリングの内容は共有しています。']); ?>
      <ul class="p-staff__list">
        <?php foreach ($staff as $i => $person): ?>
          <li class="p-staff-card">
            <figure class="c-frame c-frame--portrait p-staff-card__frame">
              <div class="c-frame__media">
                <img src="<?= e(asset('img/' . $person['image'])) ?>" width="1200" height="900" alt="" loading="lazy" decoding="async">
              </div>
              <figcaption class="c-frame__caption">スタッフ写真（撮影素材に差し替え）</figcaption>
            </figure>
            <div class="p-staff-card__body">
              <p class="p-staff-card__role"><?= e($person['role']) ?></p>
              <h3 class="p-staff-card__name"><?= e($person['name']) ?><span class="p-staff-card__kana"><?= e($person['kana']) ?></span></h3>
              <dl class="p-staff-card__meta">
                <div><dt>担当</dt><dd><?= e($person['charge']) ?></dd></div>
                <div><dt>好きな季節</dt><dd><?= e($person['season']) ?></dd></div>
              </dl>
              <p class="p-staff-card__message"><?= e($person['message']) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="p-faq l-section" id="faq" aria-labelledby="faq-title">
    <div class="l-container p-faq__grid">
      <div class="p-faq__first">
        <?php partial('section-head', ['en' => 'First visit', 'title' => 'はじめての方へ', 'id' => 'faq-title']); ?>
        <ol class="p-faq__points">
          <?php foreach ($faq['first_visit'] as $i => $point): ?>
            <li class="p-faq__point">
              <span class="p-faq__point-no" lang="en" aria-hidden="true"><?= e(sprintf('%02d', $i + 1)) ?></span>
              <p class="p-faq__point-title"><?= e($point['title']) ?></p>
              <p class="p-faq__point-text"><?= e($point['text']) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
      <div class="p-faq__qa">
        <h3 class="p-faq__qa-title"><span class="c-section-head__en" lang="en" aria-hidden="true">FAQ</span>よくある質問</h3>
        <div class="c-accordion js-accordion">
          <?php foreach ($faq['items'] as $item): ?>
            <div class="c-accordion__item">
              <h4 class="c-accordion__heading">
                <button type="button" class="c-accordion__trigger js-accordion-trigger" id="faq-q-<?= e($item['id']) ?>" aria-expanded="true" aria-controls="faq-a-<?= e($item['id']) ?>">
                  <span class="c-accordion__q" lang="en" aria-hidden="true">Q</span>
                  <span class="c-accordion__label"><?= e($item['q']) ?></span>
                  <span class="c-accordion__icon" aria-hidden="true"></span>
                </button>
              </h4>
              <div class="c-accordion__panel js-accordion-panel" id="faq-a-<?= e($item['id']) ?>">
                <div class="c-accordion__inner">
                  <?php foreach ($item['answer'] as $paragraph): ?>
                    <p><?= e($paragraph) ?></p>
                  <?php endforeach; ?>
                  <?php if (!empty($item['link'])): ?>
                    <p><a class="c-link-arrow" href="<?= e(url($item['link']['href'])) ?>"><?= e($item['link']['label']) ?><svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-arrow"/></svg></a></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="p-access l-section" id="access" aria-labelledby="access-title">
    <div class="l-container">
      <?php partial('section-head', ['en' => 'Access', 'title' => 'アクセス・営業時間', 'id' => 'access-title']); ?>
      <div class="p-access__grid">
        <figure class="p-access__map">
          <?php partial('map'); ?>
          <figcaption class="p-access__map-caption">
            地図はイメージです。
            <a href="<?= e(site('map_url')) ?>" target="_blank" rel="noopener">地図アプリで自由が丘駅を開く<svg class="c-icon" aria-hidden="true" focusable="false"><use href="#i-external"/></svg><span class="u-visually-hidden">（新しいタブで開きます）</span></a>
          </figcaption>
        </figure>
        <div class="p-access__info">
          <p class="p-access__status js-open-status" hidden></p>
          <dl class="p-access__list">
            <div class="p-access__row">
              <dt>住所</dt>
              <dd><address>〒<?= e(site('address.postal_code')) ?><br><?= e(site('address.region') . site('address.locality')) ?><?= e(site('address.street')) ?></address></dd>
            </div>
            <div class="p-access__row">
              <dt>アクセス</dt>
              <dd><?php foreach ((array) site('access') as $line): ?><?= e($line) ?><?php endforeach; ?></dd>
            </div>
            <div class="p-access__row">
              <dt>営業時間</dt>
              <dd><?= e(site('hours_label')) ?>（最終受付 <?= e(site('last_entry')) ?>）</dd>
            </div>
            <div class="p-access__row">
              <dt>定休日</dt>
              <dd><?= e(site('closed_label')) ?></dd>
            </div>
            <div class="p-access__row">
              <dt>電話</dt>
              <dd><a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a></dd>
            </div>
            <div class="p-access__row">
              <dt>お部屋</dt>
              <dd><?= e(site('beds')) ?>（どちらも個室です）</dd>
            </div>
            <div class="p-access__row">
              <dt>お支払い</dt>
              <dd><?= e(site('payment')) ?></dd>
            </div>
          </dl>
          <div class="p-access__route">
            <h3 class="p-access__route-title">駅からの道順</h3>
            <ol class="p-access__steps">
              <li>自由が丘駅の正面口を出て、駅前の通りをまっすぐ北へ。</li>
              <li>ひとつ目の大きな通りを右へ進みます。</li>
              <li>2本目の路地を左に入ってすぐ、白い建物の2階です。</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php partial('reserve'); ?>

</main>
<?php partial('footer', compact('page')); ?>
