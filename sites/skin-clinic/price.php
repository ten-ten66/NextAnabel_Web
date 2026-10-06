<?php
require __DIR__ . '/_init.php';

$fees = site_data('fees');

// タブ = 悩みのカテゴリ（施術は主となる category で振り分ける）＋診察料・お薬
$panels = [];
foreach (categories() as $id => $category) {
    $panels[$id] = [
        'label' => $category['label'],
        'treatments' => array_values(array_filter(treatments(), static fn (array $t): bool => $t['category'] === $id)),
    ];
}
$panels['basic'] = ['label' => '診察料・お薬', 'treatments' => []];

$page = [
    'id' => 'price',
    'title' => '料金表',
    'description' => '白磁スキンクリニックの料金表です。施術ごとの料金をすべて税込の総額で掲載しています。初診料・再診料・麻酔代、お支払い方法、キャンセル規定、コース契約のクーリング・オフについてもご確認いただけます。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '料金表'],
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'Price',
      'lead' => '掲載している料金は、すべて税込の総額です。施術の前に見積書をお渡しし、ご了承いただいてから施術を始めます。',
  ]); ?>

  <section class="l-section l-section--flush p-price" aria-labelledby="price-tables-title">
    <div class="l-container">
      <ul class="p-price__points">
        <li class="p-price__point">
          <p class="p-price__point-title">総額で表示</p>
          <p class="p-price__point-text">施術料は、消費税を含む総額です。月ごとの金額だけを示すような表示はしていません。</p>
        </li>
        <li class="p-price__point">
          <p class="p-price__point-title">施術の前に見積書</p>
          <p class="p-price__point-text">診察のあと、施術内容と総額を記載した見積書をお渡しします。その場で決める必要はありません。</p>
        </li>
        <li class="p-price__point">
          <p class="p-price__point-title">別にかかる費用も掲載</p>
          <p class="p-price__point-text">初診料・再診料・麻酔代・お薬代は、「診察料・お薬」のタブにまとめています。</p>
        </li>
      </ul>

      <h2 class="p-price__heading" id="price-tables-title">施術ごとの料金（税込）</h2>
      <div class="c-tabs js-tabs">
        <ul class="c-tabs__list js-tabs-list" aria-label="料金表の分類">
          <?php foreach ($panels as $id => $panel): ?>
            <li class="c-tabs__item"><a class="c-tabs__tab" id="tab-<?= e($id) ?>" href="#price-<?= e($id) ?>"><?= e($panel['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <div class="c-tabs__panels">
          <?php foreach ($panels as $id => $panel): ?>
            <section class="c-tabs__panel js-tabs-panel" id="price-<?= e($id) ?>" aria-labelledby="price-<?= e($id) ?>-title">
              <h3 class="c-tabs__panel-title" id="price-<?= e($id) ?>-title"><?= e($panel['label']) ?>の料金（税込）</h3>
              <?php if ($id === 'basic'): ?>
                <div class="p-price-block">
                  <table class="c-price-table" data-price-context="tax-included">
                    <caption class="c-price-table__caption">診察料・麻酔（税込）</caption>
                    <thead>
                      <tr>
                        <th scope="col">項目</th>
                        <th scope="col">料金（税込）</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($fees['basic'] as $fee): ?>
                        <tr>
                          <th scope="row"><?= e($fee['label']) ?><span class="c-price-table__sub"><?= e($fee['note']) ?></span></th>
                          <td><?= yen_html($fee['amount']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <table class="c-price-table" data-price-context="tax-included">
                    <caption class="c-price-table__caption">お薬・院内取り扱い品（税込）</caption>
                    <thead>
                      <tr>
                        <th scope="col">項目</th>
                        <th scope="col">料金（税込）</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($fees['medicine'] as $item): ?>
                        <tr>
                          <th scope="row"><?= e($item['label']) ?></th>
                          <td><?= yen_html($item['amount']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <p class="c-note">お薬は、診察で必要と判断した場合や、ご希望があった場合にのみ処方します。施術当日の診察料は施術料に含みます。</p>
                </div>
              <?php else: ?>
                <?php foreach ($panel['treatments'] as $t): ?>
                  <div class="p-price-block">
                    <div class="p-price-block__head">
                      <h4 class="p-price-block__title"><?= e($t['name']) ?></h4>
                      <a class="c-text-link" href="<?= e(url('treatment', ['slug' => $t['slug']])) ?>">施術の詳細・リスク<?= icon('arrow') ?></a>
                    </div>
                    <?php if (($t['price_table'] ?? '') === 'hair_removal'): ?>
                      <?php partial('hair-price-tables'); ?>
                    <?php else: ?>
                      <?php partial('price-table', ['t' => $t]); ?>
                    <?php endif; ?>
                    <dl class="p-price-block__meta">
                      <div><dt>回数の目安</dt><dd><?= e($t['sessions_short']) ?></dd></div>
                      <div><dt>ダウンタイム</dt><dd><?= e($t['downtime_short']) ?></dd></div>
                      <div><dt>主なリスク</dt><dd><?= e($t['risks_short']) ?></dd></div>
                    </dl>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>
        </div>
      </div>
      <p class="c-note p-price__legend"><span class="c-mark" aria-hidden="true">＊</span>印は、クーリング・オフの対象となるコースです（期間が1か月を超え、金額が5万円を超えるもの）。</p>
    </div>
  </section>

  <section class="l-section l-section--paper p-price-terms" aria-labelledby="terms-title">
    <div class="l-container">
      <h2 class="p-price__heading" id="terms-title">お支払い・キャンセルについて</h2>
      <div class="p-price-terms__grid">
        <section class="p-price-terms__block" aria-labelledby="payment-title">
          <h3 class="p-price-terms__title" id="payment-title">お支払い方法</h3>
          <dl class="p-price-terms__list">
            <?php foreach ($fees['payment'] as $method): ?>
              <div>
                <dt><?= e($method['label']) ?></dt>
                <dd><?= e($method['text']) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </section>
        <section class="p-price-terms__block" aria-labelledby="cancel-title">
          <h3 class="p-price-terms__title" id="cancel-title">キャンセル・予約の変更</h3>
          <ul class="c-dash-list">
            <?php foreach ($fees['cancel'] as $rule): ?>
              <li><?= e($rule) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
      <?php partial('cooling-off', ['level' => 'h3', 'id' => 'price-cooling-off-title']); ?>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
