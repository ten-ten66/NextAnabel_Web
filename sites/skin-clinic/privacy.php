<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'privacy',
    'title' => 'プライバシーポリシー',
    'description' => '白磁スキンクリニックのプライバシーポリシーです。個人情報と、病歴などの要配慮個人情報の取り扱い、利用目的、安全管理、Cookie・アクセス解析、開示などのご請求の窓口について定めています。',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => 'プライバシーポリシー'],
    ],
];
partial('head', compact('page'));
partial('header', compact('page'));

$sections = [
    'policy' => '基本方針',
    'collect' => '取得する個人情報',
    'purpose' => '利用目的',
    'sensitive' => '要配慮個人情報の取り扱い',
    'provide' => '第三者への提供',
    'outsource' => '業務の委託',
    'security' => '安全管理',
    'cookie' => 'Cookie・アクセス解析',
    'request' => '開示などのご請求',
    'contact' => 'お問い合わせ窓口',
    'revision' => '改定',
];
$n = 0;
$heading = static function (string $id) use ($sections, &$n): string {
    $n++;
    return '<h2 class="p-policy__title" id="' . e($id) . '-title"><span class="p-policy__num">' . $n . '.</span>' . e($sections[$id]) . '</h2>';
};
?>
<main id="main">
  <?php partial('page-head', [
      'page' => $page,
      'en' => 'Privacy Policy',
      'lead' => '患者さまの個人情報、とくに健康に関する情報を、どのように取り扱うかを定めています。',
  ]); ?>

  <div class="l-section l-section--flush">
    <div class="l-container p-policy">
      <nav class="p-policy__toc" aria-label="このページの目次">
        <p class="p-policy__toc-title">目次</p>
        <ol class="p-policy__toc-list">
          <?php foreach ($sections as $id => $label): ?>
            <li><a href="#<?= e($id) ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>

      <div class="p-policy__body">
        <section class="p-policy__section" id="policy" aria-labelledby="policy-title">
          <?= $heading('policy') ?>
          <p><?= e(site('name')) ?>（以下「当院」）は、患者さまの個人情報を適切に取り扱うことを医療機関の大切な責務と考えています。個人情報の保護に関する法律をはじめとする関係法令やガイドラインを守り、次の方針に基づいて個人情報を取り扱います。</p>
        </section>

        <section class="p-policy__section" id="collect" aria-labelledby="collect-title">
          <?= $heading('collect') ?>
          <p>当院は、診療とご予約に必要な範囲で、次の情報を取得します。</p>
          <ul class="c-dash-list">
            <li>氏名、フリガナ、生年月日、住所、電話番号、メールアドレスなどの連絡先</li>
            <li>ご予約の希望日時、ご用件、気になる施術</li>
            <li>問診票やカウンセリングで伺う、既往歴、服用中のお薬、アレルギー、妊娠の有無など健康に関する情報</li>
            <li>診療録、施術の記録、経過を確認するための肌の写真</li>
            <li>お会計・お支払いに関する情報</li>
            <li>当サイトの閲覧状況（Cookie などにより取得する情報）</li>
          </ul>
        </section>

        <section class="p-policy__section" id="purpose" aria-labelledby="purpose-title">
          <?= $heading('purpose') ?>
          <p>取得した個人情報は、次の目的の範囲内で利用します。</p>
          <ul class="c-dash-list">
            <li>ご予約の受付、日時の調整、ご連絡</li>
            <li>診察・カウンセリング・施術の実施と、その記録</li>
            <li>お会計、医療ローンなどのお手続き</li>
            <li>施術後の経過の確認と、それに伴うご連絡</li>
            <li>医療安全の確保と業務の改善（個人を特定できない形に加工して利用します）</li>
            <li>法令に基づく対応</li>
          </ul>
        </section>

        <section class="p-policy__section p-policy__section--emphasis" id="sensitive" aria-labelledby="sensitive-title">
          <?= $heading('sensitive') ?>
          <p>病歴や身体の状態など、健康に関する情報の多くは、法令上の「要配慮個人情報」に当たります。当院では、これらの情報を次の方針で取り扱います。</p>
          <ul class="c-check-list">
            <li>取得するときは利用目的をお伝えし、ご本人の同意を得てから取得します。Web予約フォームでは、送信の前に同意欄で取り扱いへの同意を確認しています。</li>
            <li>ご予約の受付と、カウンセリング・診療のためにのみ利用します。</li>
            <li>広告・宣伝のために利用したり、ご本人の同意なく第三者に提供したりすることはありません。</li>
            <li>閲覧できる職員を限り、診療録と同じ水準で管理します。</li>
          </ul>
        </section>

        <section class="p-policy__section" id="provide" aria-labelledby="provide-title">
          <?= $heading('provide') ?>
          <p>当院は、次の場合を除き、ご本人の同意なく個人情報を第三者に提供しません。</p>
          <ul class="c-dash-list">
            <li>法令に基づく場合</li>
            <li>人の生命、身体または財産の保護のために必要で、ご本人の同意を得ることが難しい場合</li>
            <li>公衆衛生の向上のために特に必要で、ご本人の同意を得ることが難しい場合</li>
            <li>国の機関や地方公共団体などが法令の定める事務を行うことに協力する必要がある場合</li>
          </ul>
          <p>ほかの医療機関へのご紹介にあたって診療情報を提供する場合は、事前にご本人の同意をいただきます。</p>
        </section>

        <section class="p-policy__section" id="outsource" aria-labelledby="outsource-title">
          <?= $heading('outsource') ?>
          <p>予約の管理、メールの配信、決済、医療ローンの手続きなど、業務の一部を外部に委託する場合があります。委託先は個人情報を適切に取り扱う事業者を選び、契約で安全管理を求めるとともに、必要な監督を行います。</p>
        </section>

        <section class="p-policy__section" id="security" aria-labelledby="security-title">
          <?= $heading('security') ?>
          <ul class="c-dash-list">
            <li>Web予約フォームの内容は、通信を暗号化（TLS）して送信しています。</li>
            <li>個人情報を扱う端末やシステムは、利用できる職員を限り、操作の記録を残しています。</li>
            <li>職員には、個人情報の取り扱いについて定期的に研修を行っています。</li>
            <li>紙の書類は施錠できる場所に保管し、保存期間を過ぎたものは裁断または溶解して廃棄します。</li>
          </ul>
        </section>

        <section class="p-policy__section" id="cookie" aria-labelledby="cookie-title">
          <?= $heading('cookie') ?>
          <p>当サイトでは、利用状況を把握してサイトを改善するために、アクセス解析ツールを使用する場合があります。アクセス解析ツールは Cookie を使ってページの閲覧状況などを収集しますが、氏名など個人を特定する情報は含みません。Cookie は、ブラウザーの設定で無効にできます。</p>
          <p>Web予約フォームでは、二重送信やなりすましによる送信を防ぐために Cookie（セッション）を使用しています。Cookie を無効にすると、フォームを送信できない場合があります。</p>
          <p>また、文字の表示に Google Fonts を利用しています。フォントを読み込む際に、閲覧している端末のIPアドレスなどが Google に送信されます。</p>
        </section>

        <section class="p-policy__section" id="request" aria-labelledby="request-title">
          <?= $heading('request') ?>
          <p>ご本人から、個人情報の利用目的の通知、開示、訂正・追加・削除、利用の停止、第三者への提供の停止を求められた場合は、ご本人であることを確認したうえで、法令に従って対応します。診療録の開示をご希望の方は、受付でお申し出ください。</p>
        </section>

        <section class="p-policy__section" id="contact" aria-labelledby="contact-title">
          <?= $heading('contact') ?>
          <p>個人情報の取り扱いについてのご質問やご相談は、次の窓口で承ります。</p>
          <dl class="p-policy__desk">
            <div><dt>窓口</dt><dd><?= e(site('name')) ?> 個人情報相談窓口</dd></div>
            <div><dt>所在地</dt><dd>〒<?= e(site('address.postal_code')) ?> <?= e(full_address()) ?></dd></div>
            <div><dt>電話</dt><dd><a href="<?= e(tel_href()) ?>"><?= e(site('tel')) ?></a>（<?= e(hours_summary()) ?>／休診日 <?= e(site('closed_label')) ?>）</dd></div>
            <div><dt>メール</dt><dd><a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></dd></div>
          </dl>
        </section>

        <section class="p-policy__section" id="revision" aria-labelledby="revision-title">
          <?= $heading('revision') ?>
          <p>この方針は、法令の改正などに応じて見直し、変更することがあります。変更した場合は、当サイトでお知らせします。</p>
          <p class="p-policy__dates">制定日 <?= ja_time_tag('2022-04-01') ?><br>最終改定日 <?= ja_time_tag('2026-09-30') ?></p>
        </section>
      </div>
    </div>
  </div>
</main>
<?php partial('footer', compact('page')); ?>
