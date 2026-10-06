<?php
require __DIR__ . '/_init.php';

$menu = require __DIR__ . '/data/menu.php';

// 構造化データ（料金はすべて税込）
$offers = [];
foreach ($menu['categories'] as $category) {
    foreach ($category['items'] as $item) {
        foreach ($item['plans'] as $plan) {
            $offers[] = [
                '@type' => 'Offer',
                'itemOffered' => ['@type' => 'Service', 'name' => $item['name'] . ' ' . minutes($plan['minutes'])],
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'price' => $plan['amount'],
                    'priceCurrency' => 'JPY',
                    'valueAddedTaxIncluded' => true,
                ],
            ];
        }
    }
}

$sections = [];
foreach ($menu['categories'] as $category) {
    $sections[] = ['id' => $category['id'], 'label' => $category['label']];
}
$sections[] = ['id' => 'options', 'label' => 'オプション'];
$sections[] = ['id' => 'course', 'label' => '回数券・コース'];
$sections[] = ['id' => 'notes', 'label' => 'ご案内'];

$page = [
    'id' => 'menu',
    'title' => 'メニューと料金',
    'description' => '苔と麻のメニューと料金。フェイシャル・ボディトリートメント・ヘッドスパ、オプション、回数券・コースの料金を、すべて税込の総額で掲載しています。コース契約のクーリング・オフについてもご案内します。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'メニューと料金'],
    ],
    'jsonld' => [[
        '@type' => 'OfferCatalog',
        'name' => site('name') . 'のメニューと料金',
        'itemListElement' => $offers,
    ]],
    'vendor' => ['gsap'],
    'has_reserve' => true,
    'bodyClass' => 'is-menu',
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">

  <div class="p-page-hero">
    <div class="p-page-hero__visual" aria-hidden="true">
      <img class="p-page-hero__img" src="<?= e(asset('img/leaves-room.webp')) ?>" width="1200" height="900" alt="" fetchpriority="high" decoding="async">
    </div>
    <div class="l-container p-page-hero__inner">
      <nav class="c-breadcrumb" aria-label="パンくずリスト">
        <ol class="c-breadcrumb__list">
          <li class="c-breadcrumb__item"><a href="<?= e(url('index')) ?>">ホーム</a></li>
          <li class="c-breadcrumb__item" aria-current="page">メニューと料金</li>
        </ol>
      </nav>
      <p class="c-section-head__en" lang="en" aria-hidden="true" data-intro="text" style="--intro-delay: .05s">Menu &amp; Price</p>
      <h1 class="p-page-hero__title" data-intro="text" style="--intro-delay: .12s">メニューと料金</h1>
      <p class="p-page-hero__lead" data-intro="text" style="--intro-delay: .2s">表示している料金は、すべて税込の総額です。カウンセリングの料金はいただいていません。迷ったときは、「季節のフェイシャル」か「森のヘッドスパ」から始めてみてください。</p>
    </div>
  </div>

  <nav class="p-price-nav js-price-nav" aria-label="料金表の目次">
    <ul class="p-price-nav__list l-container">
      <?php foreach ($sections as $section): ?>
        <li><a class="p-price-nav__link" href="#<?= e($section['id']) ?>"><?= e($section['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="l-container p-price">

    <?php foreach ($menu['categories'] as $category): ?>
      <section class="p-price__section js-price-section" id="<?= e($category['id']) ?>" aria-labelledby="<?= e($category['id']) ?>-title">
        <div class="p-price__head">
          <p class="c-section-head__en" lang="en" aria-hidden="true"><?= e($category['en']) ?></p>
          <h2 class="p-price__title" id="<?= e($category['id']) ?>-title"><?= e($category['label']) ?></h2>
          <p class="p-price__lead"><?= e($category['lead']) ?></p>
        </div>
        <table class="c-price-table" data-price-context="tax-included">
          <caption class="c-price-table__caption"><?= e($category['label']) ?>の料金（税込）</caption>
          <thead>
            <tr>
              <th scope="col">メニュー</th>
              <th scope="col" class="c-price-table__time">時間</th>
              <th scope="col" class="c-price-table__price">料金（税込）</th>
            </tr>
          </thead>
          <?php foreach ($category['items'] as $item): ?>
            <tbody class="c-price-table__group">
              <?php foreach ($item['plans'] as $j => $plan): ?>
                <tr>
                  <?php if ($j === 0): ?>
                    <th scope="rowgroup" rowspan="<?= e(count($item['plans'])) ?>" class="c-price-table__name">
                      <span class="c-price-table__item"><?= e($item['name']) ?></span>
                      <span class="c-price-table__summary"><?= e($item['summary']) ?></span>
                    </th>
                  <?php endif; ?>
                  <td class="c-price-table__time"><?= e(minutes($plan['minutes'])) ?><?php if (!empty($plan['note'])): ?><small><?= e($plan['note']) ?></small><?php endif; ?></td>
                  <td class="c-price-table__price"><?= e(yen($plan['amount'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endforeach; ?>
        </table>
      </section>
    <?php endforeach; ?>

    <section class="p-price__section js-price-section" id="options" aria-labelledby="options-title">
      <div class="p-price__head">
        <p class="c-section-head__en" lang="en" aria-hidden="true">Options</p>
        <h2 class="p-price__title" id="options-title">オプション</h2>
        <p class="p-price__lead">どのメニューにも追加できます。当日のご希望でもどうぞ。</p>
      </div>
      <table class="c-price-table" data-price-context="tax-included">
        <caption class="c-price-table__caption">オプションの料金（税込）</caption>
        <thead>
          <tr>
            <th scope="col">オプション</th>
            <th scope="col" class="c-price-table__time">時間</th>
            <th scope="col" class="c-price-table__price">料金（税込）</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menu['options'] as $option): ?>
            <tr>
              <th scope="row" class="c-price-table__name">
                <span class="c-price-table__item"><?= e($option['name']) ?></span>
                <span class="c-price-table__summary"><?= e($option['summary']) ?></span>
              </th>
              <td class="c-price-table__time"><?= e(minutes($option['minutes'])) ?></td>
              <td class="c-price-table__price"><?= e(yen($option['amount'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <section class="p-price__section js-price-section" id="course" aria-labelledby="course-title">
      <div class="p-price__head">
        <p class="c-section-head__en" lang="en" aria-hidden="true">Course</p>
        <h2 class="p-price__title" id="course-title">回数券・コース</h2>
        <p class="p-price__lead">ご希望の方にだけご案内しています。有効期間のうちなら、同じカテゴリーの別のメニューにも使えます。</p>
      </div>
      <table class="c-price-table c-price-table--course" data-price-context="tax-included">
        <caption class="c-price-table__caption">回数券・コースの料金（税込）</caption>
        <thead>
          <tr>
            <th scope="col">回数券・コース</th>
            <th scope="col" class="c-price-table__time">有効期間</th>
            <th scope="col" class="c-price-table__price">料金（税込）</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menu['courses'] as $course): ?>
            <?php $qualifying = is_qualifying_course($course); ?>
            <tr<?= $qualifying ? ' data-course="qualifying"' : '' ?>>
              <th scope="row" class="c-price-table__name">
                <span class="c-price-table__item"><?= e($course['name']) ?></span>
                <span class="c-price-table__summary"><?= e($course['detail']) ?></span>
                <?php if ($qualifying): ?>
                  <span class="c-badge">クーリング・オフの対象</span>
                <?php endif; ?>
              </th>
              <td class="c-price-table__time"><?= e($course['months']) ?>か月</td>
              <td class="c-price-table__price">
                <?= e(yen($course['amount'])) ?>
                <small>1回あたり <?= e(yen(intdiv($course['amount'], $course['sessions']))) ?></small>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="p-cooling" id="cooling-off" data-disclosure="cooling-off">
        <h3 class="p-cooling__title">クーリング・オフと中途解約について</h3>
        <p class="p-cooling__lead">期間が1か月を超え、かつ金額が5万円を超えるコース契約（表で「クーリング・オフの対象」としているもの）は、特定商取引法の「特定継続的役務」に当たります。</p>
        <ul class="p-cooling__list">
          <li><strong>契約書面を受け取った日から数えて8日以内</strong>であれば、書面または電磁的記録（メールなど）により、契約を解除できます（クーリング・オフ）。理由をお伝えいただく必要はありません。</li>
          <li><strong>8日を過ぎたあとも、中途解約ができます。</strong>その場合は、法令で定められた上限の範囲で、解約手数料がかかることがあります。</li>
          <li>契約の前に概要書面を、契約のときに契約書面をお渡しします。持ち帰ってご検討いただいてかまいません。</li>
        </ul>
      </div>
    </section>

    <section class="p-price__section p-notes js-price-section" id="notes" aria-labelledby="notes-title">
      <div class="p-price__head">
        <p class="c-section-head__en" lang="en" aria-hidden="true">Notes</p>
        <h2 class="p-price__title" id="notes-title">ご案内</h2>
      </div>
      <ul class="p-notes__list">
        <li>苔と麻はエステティックサロンです。医療機関ではないため、診断や医療行為は行いません。</li>
        <li data-lint-ignore>肌や体の症状が気になるときは、医療機関での診察・治療を優先してください。</li>
        <li>施術の感じ方や、効果の感じ方には個人差があります。</li>
        <li>妊娠中の方、持病のある方、通院中やお薬を飲んでいる方は、事前にかかりつけの医師にご相談ください。</li>
        <li>その日の体調によっては、施術の内容を変えたり、お断りしたりすることがあります。</li>
        <li>前日20時までのご連絡なら、変更・キャンセルの料金はかかりません。当日のキャンセルは、施術料金の50%を申し受けます（体調による日程変更を除きます）。</li>
        <li>お支払い：<?= e(site('payment')) ?></li>
      </ul>
    </section>

  </div>

  <?php partial('reserve'); ?>

</main>
<?php partial('footer', compact('page')); ?>
