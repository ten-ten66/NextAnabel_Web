<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'clinic',
    'title' => 'クリニックのご案内',
    'description' => '白磁スキンクリニックの院内・設備、衛生管理、アクセスのご案内です。東京メトロ表参道駅A4出口から徒歩4分。診療時間は' . hours_summary() . '、休診日は' . site('closed_label') . 'です。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'クリニックのご案内'],
    ],
    'jsonld' => [business_ld([
        'hasMap' => map_url(),
        'isAcceptingNewPatients' => true,
        'amenityFeature' => [
            ['@type' => 'LocationFeatureSpecification', 'name' => '個室のカウンセリングルーム', 'value' => true],
            ['@type' => 'LocationFeatureSpecification', 'name' => 'パウダールーム', 'value' => true],
        ],
    ])],
];
partial('head', compact('page'));
partial('header', compact('page'));

$rooms = [
    ['caption' => '受付・待合（撮影素材に差し替え）', 'title' => '受付・待合', 'text' => '予約枠に余裕をもたせ、待合室が混み合わないようにしています。お名前ではなく番号でお呼びすることもできます。'],
    ['caption' => 'カウンセリングルーム（撮影素材に差し替え）', 'title' => 'カウンセリングルーム', 'text' => '費用やリスクのご説明は、扉で仕切られた個室で行います。ご家族と一緒にお話を聞いていただけます。'],
    ['caption' => '施術室（撮影素材に差し替え）', 'title' => '施術室', 'text' => '施術室は3室あり、すべて個室です。施術後にメイクを直せるパウダールームを設けています。'],
];
$equipment = [
    ['name' => 'IPL光治療器', 'use' => 'シミ・くすみ・赤みの治療'],
    ['name' => 'HIFU（高密度焦点式超音波）機器', 'use' => 'たるみの治療'],
    ['name' => '医療用レーザー脱毛機（ダイオードレーザー）', 'use' => '医療レーザー脱毛'],
    ['name' => '肌画像の撮影・解析機器', 'use' => '診察と経過の確認'],
    ['name' => '高圧蒸気滅菌器', 'use' => '繰り返し使う器具の滅菌'],
    ['name' => '救急カート・AED', 'use' => '体調の急変への備え'],
];
$hygiene = [
    ['title' => '器具の使い捨てと滅菌', 'text' => '注射針など肌に触れる器具は使い捨てを基本とし、繰り返し使う器具は高圧蒸気滅菌器で滅菌しています。'],
    ['title' => '機器の清拭・消毒', 'text' => '照射機器のヘッドや施術ベッドは、患者さまごとにアルコールなどで清拭・消毒しています。'],
    ['title' => 'リネンの交換', 'text' => 'タオルや施術ベッドのシーツは、患者さまごとに交換しています。'],
    ['title' => '換気と空調', 'text' => 'すべての部屋に換気設備を設けています。待合室の混雑を避けるよう、予約枠を調整しています。'],
    ['title' => '薬剤の管理', 'text' => '薬剤は温度を管理した保管庫に保管し、使用期限と開封日を記録しています。'],
    ['title' => '急変時への備え', 'text' => '救急カートとAEDを備え、スタッフは定期的に救急対応の訓練を受けています。必要に応じて、近隣の医療機関と連携します。'],
];
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'Clinic',
      'lead' => '表参道駅から徒歩4分。白を基調にした落ち着いた院内で、カウンセリングから施術まで個室でお受けいただけます。',
  ]); ?>

  <section class="l-section l-section--flush p-clinic-rooms" aria-labelledby="rooms-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Interior', 'title' => '院内・設備', 'id' => 'rooms-title']); ?>
      </div>
      <div class="l-rail__body">
        <ul class="p-clinic-rooms__list">
          <?php foreach ($rooms as $i => $room): ?>
            <li class="p-clinic-rooms__item">
              <?php partial('photo', ['src' => 'img/interior.webp', 'width' => 1200, 'height' => 800, 'caption' => '院内写真：' . $room['caption'], 'ratio' => $i === 0 ? '16 / 9' : '4 / 3']); ?>
              <h3 class="p-clinic-rooms__title"><?= e($room['title']) ?></h3>
              <p class="p-clinic-rooms__text"><?= e($room['text']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="p-clinic-equipment">
          <h3 class="p-clinic-equipment__title">主な設備</h3>
          <table class="c-spec-table">
            <caption class="u-visually-hidden">主な設備と用途</caption>
            <thead>
              <tr>
                <th scope="col">設備</th>
                <th scope="col">用途</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($equipment as $item): ?>
                <tr>
                  <th scope="row"><?= e($item['name']) ?></th>
                  <td><?= e($item['use']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p class="c-note">機器はいずれも一般名称で記載しています。</p>
        </div>
      </div>
    </div>
  </section>

  <section class="l-section l-section--mist p-clinic-hygiene" aria-labelledby="hygiene-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Hygiene', 'title' => '衛生管理', 'id' => 'hygiene-title']); ?>
      </div>
      <div class="l-rail__body">
        <p class="p-clinic-hygiene__lead">肌に直接触れる施術だからこそ、見えないところの管理を大切にしています。</p>
        <ul class="p-clinic-hygiene__list">
          <?php foreach ($hygiene as $item): ?>
            <li class="p-clinic-hygiene__item">
              <h3 class="p-clinic-hygiene__title"><?= e($item['title']) ?></h3>
              <p class="p-clinic-hygiene__text"><?= e($item['text']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>

  <section class="l-section p-clinic-access" id="access" aria-labelledby="access-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Access', 'title' => 'アクセス', 'id' => 'access-title']); ?>
      </div>
      <div class="l-rail__body p-clinic-access__grid">
        <figure class="p-clinic-access__map">
          <?php partial('access-map', ['prefix' => 'clinic-map']); ?>
          <figcaption class="c-note">地図は位置関係を示すイラストです。縮尺は正確ではありません。</figcaption>
        </figure>
        <div class="p-clinic-access__info">
          <dl class="p-clinic-access__list">
            <div>
              <dt>所在地</dt>
              <dd><address>〒<?= e(site('address.postal_code')) ?><br><?= e(full_address()) ?></address></dd>
            </div>
            <div>
              <dt>最寄り駅</dt>
              <dd>
                <ul class="c-dash-list">
                  <?php foreach ((array) site('access') as $route): ?>
                    <li><?= e($route) ?></li>
                  <?php endforeach; ?>
                </ul>
              </dd>
            </div>
            <div>
              <dt>電話</dt>
              <dd><a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a></dd>
            </div>
            <div>
              <dt>駐車場</dt>
              <dd>専用の駐車場はありません。近くのコインパーキングをご利用ください。</dd>
            </div>
          </dl>
          <h3 class="p-clinic-access__heading">駅からの道順</h3>
          <ol class="p-clinic-access__route">
            <li>表参道駅のA4出口を出て、青山通りを外苑前方面へ約250m進みます。</li>
            <li>1本目の角を右に曲がり、約80m進みます。</li>
            <li>左手にある白い外壁の「サンプルビル」の3階です。エレベーターをご利用ください。</li>
          </ol>
          <p class="c-note">外苑前駅の1a出口からは、青山通りを表参道方面へ約550m進み、2本目の角を左に曲がってください。ビルの入口に段差が1段あります。車いすでお越しの方は、事前にお電話でご相談ください。</p>
          <a class="c-button c-button--ghost" href="<?= e(map_url()) ?>" target="_blank" rel="noopener">地図アプリで開く<?= icon('external') ?><span class="u-visually-hidden">（新しいタブで開きます）</span></a>
        </div>
      </div>
    </div>
  </section>

  <section class="l-section l-section--tint p-clinic-hours" id="hours" aria-labelledby="hours-title">
    <div class="l-container l-rail">
      <div class="l-rail__head">
        <?php partial('section-head', ['en' => 'Hours', 'title' => '診療時間', 'id' => 'hours-title']); ?>
      </div>
      <div class="l-rail__body p-clinic-hours__grid">
        <?php partial('hours-table', ['caption' => '曜日ごとの診療時間']); ?>
        <div class="p-clinic-hours__notes">
          <p class="p-clinic-hours__lead">診療は予約制です。</p>
          <ul class="c-dash-list">
            <li>休診日は<?= e(site('closed_label')) ?>です。年末年始は別途お知らせします。</li>
            <li>最終受付は、診療終了の<?= e(site('last_entry_minutes')) ?>分前です。施術の内容によっては、それより早い時間が最終受付になります。</li>
            <li>Webフォームでのご予約は24時間受け付けています。内容を確認のうえ、2営業日以内にご連絡します。</li>
          </ul>
          <a class="c-button c-button--primary" href="<?= e(url('contact')) ?>">カウンセリングを予約する<?= icon('arrow') ?></a>
        </div>
      </div>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
