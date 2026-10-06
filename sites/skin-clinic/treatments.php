<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'treatments',
    'title' => '施術一覧',
    'description' => '白磁スキンクリニックの施術一覧です。IPL光治療、ケミカルピーリング、HIFU、ヒアルロン酸注入、医療レーザー脱毛について、悩みごとに料金（税込）・回数の目安・ダウンタイム・主なリスクをまとめています。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '施術一覧'],
    ],
    'jsonld' => [
        [
            '@type' => 'ItemList',
            'name' => '施術一覧',
            'itemListElement' => array_map(static fn (array $t, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $t['name'],
                'url' => absolute_url(url('treatment', ['slug' => $t['slug']])),
            ], treatments(), array_keys(treatments())),
        ],
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'Treatments',
      'lead' => '悩みを選ぶと、対応する施術に絞り込めます。料金はすべて税込の総額で、回数の目安やダウンタイム、主なリスクもあわせて掲載しています。',
  ]); ?>

  <section class="l-section l-section--flush p-treatments" aria-labelledby="treatments-list-title">
    <div class="l-container">
      <h2 class="u-visually-hidden" id="treatments-list-title">施術の一覧</h2>
      <?php partial('tx-filter', ['target' => 'treatment-list']); ?>
      <ul class="c-tx-list" id="treatment-list">
        <?php foreach (treatments() as $t): ?>
          <?php partial('tx-card', ['t' => $t, 'variant' => 'row']); ?>
        <?php endforeach; ?>
      </ul>
      <div class="p-treatments__notes">
        <p class="c-note">掲載している料金は、1回あたり・コースともに税込の総額です。別途、初診料・再診料がかかります。</p>
        <p class="c-note">診察の結果、肌の状態や持病、服用中のお薬によっては施術をお受けいただけない場合があります。</p>
        <p class="c-note">回数・期間の目安やダウンタイムには個人差があります。詳しくは各施術のページと、カウンセリングでご説明します。</p>
      </div>
      <div class="p-treatments__links">
        <a class="c-button c-button--ghost" href="<?= e(url('price')) ?>">料金表を見る<?= icon('arrow') ?></a>
        <a class="c-button c-button--ghost" href="<?= e(url('faq')) ?>">よくあるご質問<?= icon('arrow') ?></a>
      </div>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
