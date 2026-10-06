<?php
require __DIR__ . '/_init.php';

$cases = require __DIR__ . '/data/cases.php';

$page = [
    'id' => 'cases',
    'title' => '症例写真',
    'description' => 'オルヴァン美容外科の症例写真の掲載方針です。掲載は患者様の同意を得た写真のみとし、治療内容・費用（税込）・主なリスクと副作用・経過期間を、写真のすぐ隣に記載します。写真の加工・修整は行いません。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '症例写真'],
    ],
];

$policies = [
    ['title' => '掲載するのは、同意を得た写真だけ', 'text' => '症例写真は、掲載の目的・範囲・期間をご説明し、書面で同意をいただいた患者様の写真に限って掲載します。同意はいつでも撤回でき、お申し出があった写真は速やかに削除します。'],
    ['title' => '治療内容・費用・リスクを、写真の隣に', 'text' => '写真だけが独り歩きしないよう、治療内容、費用（税込）、主なリスク・副作用、撮影時期、担当医を、すべての写真のすぐ隣に記載します。'],
    ['title' => '写真の加工・修整はしない', 'text' => '明るさや色味の補正を含め、写真の加工は行いません。照明・角度・距離をそろえて撮影し、術前と術後を同じ条件で見比べられるようにしています。'],
];

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'kicker' => 'Cases',
      'title' => '症例写真',
      'lead' => '症例写真は、施術を検討するうえでの参考のひとつです。仕上がりや経過には個人差があるため、当院では写真と同じ大きさで、治療内容・費用・リスクを並べて掲載しています。',
  ]); ?>

  <section class="p-policy l-section" aria-labelledby="policy-title">
    <div class="l-container">
      <div class="c-section-head c-section-head--split">
        <p class="c-kicker"><span class="c-kicker__index">01</span><span class="c-kicker__label" lang="en">Policy</span></p>
        <h2 class="c-section-head__title" id="policy-title">掲載方針</h2>
        <p class="c-section-head__lead">医療広告に関するガイドラインを踏まえ、症例写真の掲載には次の3つの決まりを設けています。</p>
      </div>
      <ol class="p-policy__list">
        <?php foreach ($policies as $i => $policy): ?>
          <li class="p-policy__item">
            <span class="c-numeral p-policy__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span>
            <h3 class="p-policy__title"><?= e($policy['title']) ?></h3>
            <p class="p-policy__text"><?= e($policy['text']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="p-case-list l-section" aria-labelledby="case-list-title">
    <div class="l-container">
      <div class="c-section-head c-section-head--split">
        <p class="c-kicker"><span class="c-kicker__index">02</span><span class="c-kicker__label" lang="en">Template</span></p>
        <h2 class="c-section-head__title" id="case-list-title">症例写真の掲載テンプレート</h2>
        <p class="c-section-head__lead">実際の掲載時と同じレイアウトの見本です。写真枠には、患者様の同意を得た写真が入ります（このサンプルでは写真を掲載していません）。</p>
      </div>

      <div class="p-case-list__items">
        <?php foreach ($cases as $i => $case): ?>
          <article class="p-case" id="<?= e($case['id']) ?>" aria-labelledby="<?= e($case['id']) ?>-title" data-case>
            <header class="p-case__head">
              <p class="p-case__no"><span class="p-case__no-label" lang="en">Case</span><span class="c-numeral p-case__no-num"><?= e(sc_num($i + 1)) ?></span></p>
              <h3 class="p-case__title" id="<?= e($case['id']) ?>-title"><?= e($case['title']) ?></h3>
              <p class="p-case__patient"><?= e($case['patient']) ?></p>
            </header>
            <div class="p-case__photos">
              <figure class="p-case__photo" data-case-photo="before">
                <div class="p-case__frame">
                  <span class="p-case__frame-label">術前</span>
                  <span class="p-case__frame-note">症例写真（掲載時は実際の写真）</span>
                </div>
                <figcaption class="p-case__photo-caption">術前</figcaption>
              </figure>
              <figure class="p-case__photo" data-case-photo="after">
                <div class="p-case__frame">
                  <span class="p-case__frame-label">術後</span>
                  <span class="p-case__frame-note">症例写真（掲載時は実際の写真）</span>
                </div>
                <figcaption class="p-case__photo-caption"><?= e($case['after_label']) ?></figcaption>
              </figure>
            </div>
            <dl class="p-case__info">
              <div class="p-case__row p-case__row--wide">
                <dt>ご相談内容</dt>
                <dd><?= e($case['concern']) ?></dd>
              </div>
              <div class="p-case__row p-case__row--wide" data-disclosure="treatment">
                <dt>治療内容</dt>
                <dd><?= e($case['treatment']) ?></dd>
              </div>
              <div class="p-case__row" data-disclosure="price">
                <dt>費用（税込）</dt>
                <dd>
                  <span class="p-case__total"><?= e(tax_in($case['total'])) ?></span>
                  <?php if (count($case['prices']) > 1): ?>
                    <span class="p-case__breakdown">内訳：<?php foreach ($case['prices'] as $j => $price): ?><?= $j > 0 ? '、' : '' ?><?= e($price['label'] . ' ' . tax_in($price['amount'])) ?><?php endforeach; ?></span>
                  <?php endif; ?>
                  <span class="p-case__breakdown"><?= e($case['price_note']) ?></span>
                </dd>
              </div>
              <div class="p-case__row" data-disclosure="risks">
                <dt>主なリスク・副作用</dt>
                <dd><?= e($case['risks']) ?></dd>
              </div>
              <div class="p-case__row">
                <dt>経過期間</dt>
                <dd><?= e($case['period']) ?></dd>
              </div>
              <div class="p-case__row">
                <dt>担当医</dt>
                <dd><?= e($case['doctor']) ?></dd>
              </div>
            </dl>
            <p class="p-case__link"><a class="c-link" href="<?= e(url('treatment', ['slug' => $case['slug']])) ?>">この施術の詳細（費用・リスク・ダウンタイム）</a></p>
          </article>
        <?php endforeach; ?>
      </div>

      <ul class="c-notes p-case-list__notes">
        <li>仕上がりや経過には個人差があります。症例写真は、同じ結果が得られることを示すものではありません。</li>
        <li>写真は、患者様の同意を得たうえで、撮影条件をそろえて撮影しています。加工・修整は行っていません。</li>
        <li>掲載している費用は、撮影時点の税込価格です。</li>
      </ul>
    </div>
  </section>

  <?php partial('cta'); ?>
</main>
<?php partial('footer'); ?>
