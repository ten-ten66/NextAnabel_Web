<?php
require __DIR__ . '/_init.php';

$groups = site_data('faq');

$entities = [];
foreach ($groups as $group) {
    foreach ($group['items'] as $item) {
        $entities[] = [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
        ];
    }
}

$page = [
    'id' => 'faq',
    'title' => 'よくあるご質問',
    'description' => '白磁スキンクリニックによくお寄せいただくご質問をまとめました。予約・カウンセリング、費用とお支払い、施術、キャンセル、未成年の方の同意書、妊娠中・授乳中の施術などについてお答えします。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'よくあるご質問'],
    ],
    // 構造を正しく伝えるための記述（リッチリザルトの表示を目的としたものではない）
    'jsonld' => [['@type' => 'FAQPage', 'mainEntity' => $entities]],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'FAQ',
      'lead' => 'ご予約や費用、施術についてよくいただくご質問です。ここにない内容は、お電話またはWebフォームからお気軽にお問い合わせください。',
  ]); ?>

  <div class="l-section l-section--flush">
    <div class="l-container p-faq">
      <nav class="p-faq__index" aria-label="質問の分類">
        <p class="p-faq__index-title">分類から探す</p>
        <ul class="p-faq__index-list">
          <?php foreach ($groups as $group): ?>
            <li><a class="p-faq__index-link" href="#faq-<?= e($group['id']) ?>"><?= e($group['title']) ?><span class="p-faq__index-count"><?= e(count($group['items'])) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="p-faq__groups">
        <?php foreach ($groups as $group): ?>
          <section class="p-faq__group" id="faq-<?= e($group['id']) ?>" aria-labelledby="faq-<?= e($group['id']) ?>-title">
            <h2 class="p-faq__title" id="faq-<?= e($group['id']) ?>-title"><?= e($group['title']) ?></h2>
            <div class="c-accordion">
              <?php foreach ($group['items'] as $item): ?>
                <details class="c-accordion__item js-accordion">
                  <summary class="c-accordion__summary">
                    <span class="c-accordion__mark" aria-hidden="true">Q</span>
                    <span class="c-accordion__question"><?= e($item['q']) ?></span>
                    <span class="c-accordion__icon" aria-hidden="true"></span>
                  </summary>
                  <div class="c-accordion__body">
                    <span class="c-accordion__mark c-accordion__mark--answer" aria-hidden="true">A</span>
                    <p class="c-accordion__answer"><?= e($item['a']) ?></p>
                  </div>
                </details>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
        <div class="p-faq__more">
          <p class="p-faq__more-text">施術ごとの費用やリスクは、各施術のページに詳しく掲載しています。</p>
          <ul class="p-faq__more-links">
            <li><a class="c-text-link" href="<?= e(url('treatments')) ?>">施術一覧<?= icon('arrow') ?></a></li>
            <li><a class="c-text-link" href="<?= e(url('price')) ?>">料金表<?= icon('arrow') ?></a></li>
            <li><a class="c-text-link" href="<?= e(url('contact')) ?>">ご予約・お問い合わせ<?= icon('arrow') ?></a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <?php partial('cta'); ?>
</main>
<?php partial('footer', compact('page')); ?>
