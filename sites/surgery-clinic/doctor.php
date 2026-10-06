<?php
require __DIR__ . '/_init.php';

$doctor = require __DIR__ . '/data/doctor.php';

$page = [
    'id' => 'doctor',
    'title' => '医師紹介',
    'description' => 'オルヴァン美容外科 院長 桐生 遼（形成外科専門医）のプロフィールです。大学病院の形成外科で再建手術に携わってきた経験をもとに、カウンセリングから手術、術後の検診までを担当します。',
    'type' => 'profile',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '医師紹介'],
    ],
    'jsonld' => [
        business_ld(),
        [
            '@type' => 'ProfilePage',
            'url' => absolute_url(),
            'name' => '医師紹介',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $doctor['name'],
                'jobTitle' => $doctor['role'],
                'hasCredential' => array_map(
                    static fn (string $c): array => ['@type' => 'EducationalOccupationalCredential', 'name' => $c],
                    array_slice($doctor['credentials'], 0, 2)
                ),
                'knowsAbout' => array_column(sc_treatments(), 'name'),
                'worksFor' => ['@id' => absolute_url('') . '#business'],
            ],
        ],
    ],
];

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'kicker' => 'Doctor',
      'title' => '医師紹介',
      'lead' => '執刀する医師が、カウンセリングで最初にお話を伺い、術後の検診まで経過を診ていきます。',
  ]); ?>

  <section class="p-profile l-section" aria-labelledby="profile-title">
    <div class="l-container p-profile__layout">
      <?php partial('frame', ['src' => 'img/portrait-frame.webp', 'width' => 800, 'height' => 1000, 'shape' => 'portrait', 'caption' => '医師写真（撮影素材に差し替え）', 'class' => 'p-profile__photo']); ?>
      <div class="p-profile__body">
        <h2 class="p-profile__name" id="profile-title">
          <span class="p-profile__role"><?= e($doctor['role']) ?></span>
          <span class="p-profile__fullname"><?= e($doctor['name']) ?></span>
        </h2>
        <p class="p-profile__kana"><?= e($doctor['kana']) ?> ／ <span lang="en"><?= e($doctor['name_en']) ?></span></p>
        <p class="p-profile__license"><?= e($doctor['license']) ?></p>
        <div class="p-profile__message">
          <?php foreach ($doctor['message'] as $paragraph): ?>
            <p><?= e($paragraph) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="p-principles l-section" aria-labelledby="principles-title">
    <div class="l-container">
      <div class="c-section-head c-section-head--split">
        <p class="c-kicker"><span class="c-kicker__index">01</span><span class="c-kicker__label" lang="en">Principles</span></p>
        <h2 class="c-section-head__title" id="principles-title">診療で大切にしていること</h2>
      </div>
      <ol class="p-philosophy__list">
        <?php foreach ($doctor['principles'] as $i => $item): ?>
          <li class="p-philosophy__item">
            <span class="c-numeral p-philosophy__num" aria-hidden="true" data-drift><?= e(sc_num($i + 1)) ?></span>
            <h3 class="p-philosophy__title"><?= e($item['title']) ?></h3>
            <p class="p-philosophy__text"><?= e($item['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="p-career l-section" aria-labelledby="career-title">
    <div class="l-container p-career__layout">
      <div class="c-section-head">
        <p class="c-kicker"><span class="c-kicker__index">02</span><span class="c-kicker__label" lang="en">Career</span></p>
        <h2 class="c-section-head__title" id="career-title">経歴・資格</h2>
      </div>
      <div class="p-career__body">
        <ol class="p-career__timeline">
          <?php foreach ($doctor['career'] as $row): ?>
            <li class="p-career__row">
              <span class="c-digits p-career__year"><?= e($row['year']) ?></span>
              <span class="p-career__text"><?= e($row['text']) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
        <h3 class="p-career__subtitle">資格・所属学会</h3>
        <ul class="c-dash-list">
          <?php foreach ($doctor['credentials'] as $credential): ?><li><?= e($credential) ?></li><?php endforeach; ?>
        </ul>
        <p class="c-note">施術ページの内容は、院長が医学的な観点から確認し、ページごとに最終確認日を記載しています。</p>
      </div>
    </div>
  </section>

  <?php partial('cta', ['heading' => '執刀医に、直接ご相談ください。']); ?>
</main>
<?php partial('footer'); ?>
