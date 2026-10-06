<?php
require __DIR__ . '/_init.php';

$doctor = site_data('doctor');

$page = [
    'id' => 'doctor',
    'title' => '医師紹介',
    'description' => '白磁スキンクリニック院長・' . $doctor['name'] . '（' . $doctor['specialty'] . '）のご紹介です。診療への考え方、経歴、資格、診療方針を掲載しています。皮膚科での診断をもとに、美容皮膚科の治療をご提案します。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '医師紹介'],
    ],
    'jsonld' => [
        [
            '@type' => 'ProfilePage',
            '@id' => absolute_url() . '#webpage',
            'url' => absolute_url(),
            'name' => '医師紹介',
            'inLanguage' => 'ja',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $doctor['name'],
                'alternateName' => $doctor['name_en'],
                'jobTitle' => $doctor['role'] . '・' . $doctor['specialty'],
                'worksFor' => ['@id' => absolute_url('') . '#business'],
                'knowsAbout' => array_map(static fn (array $t): string => $t['name'], treatments()),
            ],
        ],
        business_ld(),
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', ['page' => $page, 'en' => 'Doctor']); ?>

  <section class="l-section l-section--flush p-doctor-intro" aria-labelledby="doctor-message-title">
    <div class="l-container p-doctor-intro__grid">
      <div class="p-doctor-intro__visual">
        <?php partial('photo', ['src' => 'img/portrait-frame.webp', 'width' => 800, 'height' => 1000, 'caption' => '医師写真（撮影素材に差し替え）']); ?>
        <div class="p-doctor-intro__profile">
          <p class="p-doctor-intro__role"><?= e($doctor['role']) ?>／<?= e($doctor['specialty']) ?></p>
          <p class="p-doctor-intro__name"><?= e($doctor['name']) ?></p>
          <p class="p-doctor-intro__kana"><?= e($doctor['kana']) ?><span aria-hidden="true">　<?= e($doctor['name_en']) ?></span></p>
        </div>
      </div>
      <div class="p-doctor-intro__body">
        <h2 class="p-doctor-intro__title" id="doctor-message-title">ごあいさつ</h2>
        <p class="p-doctor-intro__quote"><?= e($doctor['quote']) ?></p>
        <?php foreach ($doctor['message'] as $paragraph): ?>
          <p class="p-doctor-intro__text"><?= e($paragraph) ?></p>
        <?php endforeach; ?>
        <p class="p-doctor-intro__sign"><?= e(site('name')) ?> <?= e($doctor['role']) ?><span class="p-doctor-intro__sign-name"><?= e($doctor['name']) ?></span></p>
      </div>
    </div>
  </section>

  <section class="l-section l-section--mist p-doctor-policy" aria-labelledby="policy-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Policy', 'title' => '診療方針', 'id' => 'policy-title']); ?>
      </div>
      <div class="l-rail__body">
        <ul class="p-doctor-policy__list">
          <?php foreach ($doctor['policy'] as $item): ?>
            <li class="p-doctor-policy__item">
              <h3 class="p-doctor-policy__title"><?= e($item['title']) ?></h3>
              <p class="p-doctor-policy__text"><?= e($item['text']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>

  <section class="l-section p-doctor-career" aria-labelledby="career-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Career', 'title' => '経歴・資格', 'id' => 'career-title']); ?>
      </div>
      <div class="l-rail__body p-doctor-career__grid">
        <div class="p-doctor-career__block">
          <h3 class="p-doctor-career__heading">経歴</h3>
          <dl class="c-timeline">
            <?php foreach ($doctor['career'] as $row): ?>
              <div class="c-timeline__row">
                <dt class="c-timeline__year"><?= e($row['year']) ?></dt>
                <dd class="c-timeline__text"><?= e($row['text']) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
        <div class="p-doctor-career__block">
          <h3 class="p-doctor-career__heading">資格</h3>
          <ul class="c-dash-list">
            <?php foreach ($doctor['licenses'] as $item): ?>
              <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
          <h3 class="p-doctor-career__heading">所属学会</h3>
          <ul class="c-dash-list">
            <?php foreach ($doctor['societies'] as $item): ?>
              <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
          <h3 class="p-doctor-career__heading">監修している施術ページ</h3>
          <ul class="p-doctor-career__links">
            <?php foreach (treatments() as $t): ?>
              <li><a class="c-text-link" href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>"><?= e($t['name']) ?><?= icon('arrow') ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
