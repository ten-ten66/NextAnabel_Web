<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'privacy',
    'title' => 'プライバシーポリシー',
    'description' => 'オルヴァン美容外科の個人情報の取り扱い（プライバシーポリシー）です。取得する情報、利用目的、第三者への提供、安全管理、開示等のご請求、症例写真の取り扱い、お問い合わせ窓口について定めています。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'プライバシーポリシー'],
    ],
];

$sections = [
    [
        'title' => '基本方針',
        'body' => ['当院は、患者様の個人情報を適切に取り扱うことが、医療機関としての重要な責務であると考えています。個人情報の保護に関する法律、および医療・介護関係事業者における個人情報の適切な取扱いのためのガイダンスを踏まえ、以下のとおり個人情報を取り扱います。'],
    ],
    [
        'title' => '取得する個人情報',
        'body' => ['当院は、次の個人情報を、適正な手段により取得します。'],
        'list' => [
            'お名前、フリガナ、生年月日、ご住所、電話番号、メールアドレスなどの連絡先',
            'ご予約の内容（ご希望の施術・日時）、ご相談の内容',
            '問診票・診療録・検査結果・写真など、診療に関する情報',
        ],
    ],
    [
        'title' => '利用目的',
        'body' => ['取得した個人情報は、次の目的の範囲内で利用します。'],
        'list' => [
            'カウンセリング・診療のご予約の受付、日時の確認とご連絡',
            '診察・手術・術後の検診など、患者様に提供する医療サービス',
            '術後の経過確認や、緊急時のご連絡',
            'お問い合わせへの回答',
            '医療安全の確保と、診療内容の質の向上（個人を特定できない形での分析）',
            '法令に基づく届出・報告への対応',
        ],
    ],
    [
        'title' => '第三者への提供',
        'body' => ['当院は、次の場合を除き、患者様の同意を得ずに個人情報を第三者に提供しません。'],
        'list' => [
            '法令に基づく場合',
            '人の生命・身体または財産の保護のために必要で、ご本人の同意を得ることが難しい場合',
            '術後の合併症などで、他の医療機関と連携して診療を行う必要がある場合',
        ],
    ],
    [
        'title' => '業務の委託',
        'body' => ['予約管理システムの運用や検体検査など、業務の一部を外部に委託する場合があります。委託先とは個人情報の取り扱いに関する契約を結び、適切に管理・監督します。'],
    ],
    [
        'title' => '安全管理措置',
        'body' => ['個人情報への不正なアクセス、紛失、破損、改ざん、漏えいを防ぐため、職員への教育、アクセス権限の管理、通信の暗号化など、必要かつ適切な安全管理措置を講じます。'],
    ],
    [
        'title' => '症例写真の取り扱い',
        'body' => ['診療のために撮影した写真は、診療録の一部として管理します。Webサイトなどに症例写真として掲載するのは、掲載の目的・範囲・期間をご説明し、書面で同意をいただいた場合に限ります。同意はいつでも撤回でき、お申し出があった写真は速やかに掲載を取りやめます。'],
    ],
    [
        'title' => '開示・訂正・利用停止などのご請求',
        'body' => ['ご本人から、個人情報の開示・訂正・追加・削除・利用停止のご請求があった場合は、ご本人であることを確認したうえで、法令に従って速やかに対応します。ご請求の方法は、下記の窓口までお問い合わせください。'],
    ],
    [
        'title' => 'Cookie・アクセス解析について',
        'body' => ['本サイトでは、予約フォームの送信処理のためにのみ Cookie を使用します。アクセス解析ツールや広告配信のための Cookie は使用していません。'],
    ],
];

partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'kicker' => 'Privacy Policy',
      'title' => 'プライバシーポリシー',
      'lead' => '個人情報の取り扱いについて定めています。',
  ]); ?>

  <div class="l-container p-document l-section">
    <nav class="p-document__toc" aria-label="ページ内目次">
      <p class="p-treatment__toc-title" lang="en">Contents</p>
      <ol class="p-treatment__toc-list">
        <?php foreach ($sections as $i => $section): ?>
          <li><a href="#policy-<?= $i + 1 ?>"><span class="p-treatment__toc-num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span><?= e($section['title']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="#policy-contact"><span class="p-treatment__toc-num" aria-hidden="true"><?= e(sc_num(count($sections) + 1)) ?></span>お問い合わせ窓口</a></li>
      </ol>
    </nav>
    <div class="p-document__body">
      <?php foreach ($sections as $i => $section): ?>
        <section class="p-document__section" id="policy-<?= $i + 1 ?>" aria-labelledby="policy-<?= $i + 1 ?>-title">
          <h2 class="c-heading" id="policy-<?= $i + 1 ?>-title"><span class="c-heading__num" aria-hidden="true"><?= e(sc_num($i + 1)) ?></span><?= e($section['title']) ?></h2>
          <?php foreach ($section['body'] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
          <?php if (!empty($section['list'])): ?>
            <ul class="c-dash-list">
              <?php foreach ($section['list'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
      <section class="p-document__section" id="policy-contact" aria-labelledby="policy-contact-title">
        <h2 class="c-heading" id="policy-contact-title"><span class="c-heading__num" aria-hidden="true"><?= e(sc_num(count($sections) + 1)) ?></span>お問い合わせ窓口</h2>
        <dl class="c-facts">
          <div class="c-facts__item"><dt>窓口</dt><dd><?= e(site('name')) ?> 個人情報相談窓口</dd></div>
          <div class="c-facts__item"><dt>所在地</dt><dd>〒<?= e(site('address.postal_code')) ?> <?= e(site('address.region') . site('address.locality') . site('address.street')) ?></dd></div>
          <div class="c-facts__item"><dt>お電話</dt><dd><a href="<?= e(sc_tel_href()) ?>"><?= e(site('tel')) ?></a>（<?= e(site('hours_label')) ?>）</dd></div>
          <div class="c-facts__item"><dt>メール</dt><dd><a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></dd></div>
        </dl>
        <p class="p-document__date">制定日：<?= sc_date('2026-10-01') ?></p>
      </section>
    </div>
  </div>
</main>
<?php partial('footer'); ?>
