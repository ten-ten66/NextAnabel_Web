<?php
require __DIR__ . '/_init.php';

$samples = require __DIR__ . '/data/samples.php';
$metricsFile = __DIR__ . '/data/metrics.json';
$metrics = is_file($metricsFile) ? json_decode((string) file_get_contents($metricsFile), true) : null;
$lighthouse = [];
foreach ($metrics['lighthouse'] ?? [] as $row) {
    $lighthouse[$row['site']][] = $row;
}

$page = [
    'id' => 'index',
    'description' => site('description'),
    'jsonld' => [[
        '@type' => 'CollectionPage',
        'name' => site('name'),
        'url' => absolute_url(''),
        'hasPart' => array_map(static fn ($s) => [
            '@type' => 'WebSite',
            'name' => $s['name'] . '（架空）',
            'url' => absolute_url($s['href']),
        ], $samples),
    ]],
];

/** 0〜100 のスコアを3段階で表す */
function score_class(?int $score): string
{
    return match (true) {
        $score === null => 'is-none',
        $score >= 90 => 'is-good',
        $score >= 50 => 'is-fair',
        default => 'is-poor',
    };
}

partial('head', compact('page'));
?>
<header class="p-intro">
  <div class="l-wrap p-intro__inner">
    <p class="c-label">Sample works</p>
    <h1 class="p-intro__title">美容・医療 Webサイト<br class="u-sp-hidden"> サンプル集</h1>
    <p class="p-intro__lead">美容皮膚科・美容外科・エステサロン・医療脱毛LPの、4つのサンプルサイトです。すべて架空のブランドで制作し、実際の制作で求められる広告表現への配慮、予約までの導線、表示速度、公開後の更新しやすさまで形にしています。</p>
    <dl class="p-intro__facts">
      <div><dt>サンプル</dt><dd>4サイト・<?= e((string) ($metrics['pageCount'] ?? '—')) ?>ページ</dd></div>
      <div><dt>ブランド・人物</dt><dd>すべて架空</dd></div>
      <div><dt>最終更新</dt><dd><?= time_tag((string) ($metrics['measuredAt'] ?? '2026-10-06'), 'Y年n月j日') ?></dd></div>
    </dl>
  </div>
</header>

<main id="main">
  <section class="p-works" aria-labelledby="works-title">
    <div class="l-wrap">
      <h2 id="works-title" class="c-heading">サンプル一覧</h2>
      <ul class="p-works__list">
        <?php foreach ($samples as $sample): ?>
          <li class="c-work">
            <a class="c-work__link" href="<?= e($sample['href']) ?>">
              <figure class="c-work__media">
                <img class="c-work__desktop" src="<?= e(asset('img/thumb-' . $sample['id'] . '-desktop.webp')) ?>" width="960" height="600" alt="" loading="lazy" decoding="async">
                <img class="c-work__mobile" src="<?= e(asset('img/thumb-' . $sample['id'] . '-mobile.webp')) ?>" width="240" height="520" alt="" loading="lazy" decoding="async">
              </figure>
              <span class="c-work__type"><?= e($sample['type']) ?></span>
              <h3 class="c-work__name"><?= e($sample['name']) ?></h3>
            </a>
            <p class="c-work__summary"><?= e($sample['summary']) ?></p>
            <ul class="c-work__tags" aria-label="特徴">
              <?php foreach ($sample['tags'] as $tag): ?>
                <li><?= e($tag) ?></li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="p-detail" aria-labelledby="detail-title">
    <div class="l-wrap">
      <h2 id="detail-title" class="c-heading">ご覧になる方に合わせた説明</h2>
      <div class="c-tabs js-tabs">
        <div class="c-tabs__list" role="tablist" aria-label="説明の切り替え" hidden>
          <button class="c-tabs__tab" type="button" role="tab" id="tab-owners" aria-controls="for-owners" aria-selected="true">クリニック・サロンの方へ</button>
          <button class="c-tabs__tab" type="button" role="tab" id="tab-studios" aria-controls="for-studios" aria-selected="false" tabindex="-1">制作会社の方へ</button>
        </div>

        <div class="c-tabs__panel" id="for-owners">
          <h3 class="c-tabs__title">規制に配慮しながら、予約につながるサイトを。</h3>
          <div class="p-values">
            <article class="p-values__item">
              <h4>広告表現への配慮</h4>
              <p>医療広告に関する厚生労働省のガイドラインや景品表示法を踏まえ、体験談・値引きの強調・誇大な表現を使わずに構成しています。自由診療の費用・リスク・副作用は、価格のすぐ隣に本文と同じ大きさで表示します。</p>
            </article>
            <article class="p-values__item">
              <h4>料金のわかりやすさ</h4>
              <p>料金はすべて税込の総額で表示しています。医療脱毛LPでは、部位を選ぶだけで総額と通院回数の目安がわかるシミュレーターを用意しました。月額だけを強調する表示はしていません。</p>
            </article>
            <article class="p-values__item">
              <h4>予約までの導線</h4>
              <p>予約フォームは入力・確認・完了の3段階で、入力の誤りはその場でお知らせします。スマートフォンでは画面下に予約ボタンを固定し、電話とWeb予約の入口を各ページに置いています。</p>
            </article>
            <article class="p-values__item">
              <h4>公開後の更新しやすさ</h4>
              <p>料金や施術内容はデータとして1か所で管理し、変更すると一覧・詳細・料金表・LPのすべてに反映されます。WordPressで院内から更新できる版のテーマも用意しています。</p>
            </article>
          </div>

          <h4 class="p-fit__title">こんな医院・サロンに</h4>
          <dl class="p-fit">
            <?php foreach ($samples as $sample): ?>
              <div class="p-fit__row">
                <dt><a href="<?= e($sample['href']) ?>"><?= e($sample['name']) ?></a></dt>
                <dd><?= e($sample['fit']) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          <p class="c-note">サンプルの表現は、一般的な規制の考え方を踏まえて作成したものです。実際の掲載前には、医療機関・事業者の皆さまと内容を確認し、必要に応じて専門家の確認を受ける前提で進めます。</p>
        </div>

        <div class="c-tabs__panel" id="for-studios">
          <h3 class="c-tabs__title">実装の中身まで、確認いただけます。</h3>

          <section class="c-table-wrap" tabindex="0" aria-label="各サンプルの技術構成">
            <table class="c-table">
              <caption>各サンプルの技術構成</caption>
              <thead>
                <tr>
                  <th scope="col">サンプル</th>
                  <?php foreach (array_keys($samples[0]['tech']) as $col): ?>
                    <th scope="col"><?= e($col) ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($samples as $sample): ?>
                  <tr>
                    <th scope="row"><?= e($sample['name']) ?></th>
                    <?php foreach ($sample['tech'] as $value): ?>
                      <td><?= e($value) ?></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </section>

          <h4 class="c-subheading">品質の計測値</h4>
          <?php if ($lighthouse): ?>
            <section class="c-table-wrap" tabindex="0" aria-label="Lighthouse の計測値">
              <table class="c-table c-table--metrics">
                <caption>Lighthouse（モバイル）の計測値。<?= time_tag((string) $metrics['measuredAt'], 'Y年n月j日') ?>計測</caption>
                <thead>
                  <tr>
                    <th scope="col">ページ</th>
                    <th scope="col">パフォーマンス</th>
                    <th scope="col">アクセシビリティ</th>
                    <th scope="col">ベストプラクティス</th>
                    <th scope="col">SEO</th>
                    <th scope="col">LCP</th>
                    <th scope="col">CLS</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($samples as $sample): ?>
                    <?php foreach ($lighthouse[$sample['id']] ?? [] as $row): ?>
                      <tr>
                        <th scope="row"><?= e($sample['name']) ?><span class="c-table__path"><?= e(basename($row['path'])) ?></span></th>
                        <?php foreach (['performance', 'accessibility', 'bestPractices', 'seo'] as $key): ?>
                          <td><span class="c-score <?= score_class($row[$key] ?? null) ?>"><?= e((string) ($row[$key] ?? '—')) ?></span></td>
                        <?php endforeach; ?>
                        <td class="u-mono"><?= e((string) ($row['lcp'] ?? '—')) ?></td>
                        <td class="u-mono"><?= e((string) ($row['cls'] ?? '—')) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </section>
          <?php endif; ?>
          <ul class="p-checks">
            <?php foreach ($metrics['checks'] ?? [] as $check): ?>
              <li><span class="p-checks__value u-mono"><?= e($check['value']) ?></span><?= e($check['label']) ?></li>
            <?php endforeach; ?>
          </ul>
          <p class="c-note">Lighthouse は本番公開を想定したビルド（検索エンジン向けの noindex を外した版）で計測しています。公開中のサンプルは架空サイトのため noindex を付けており、PageSpeed Insights で測ると SEO の値は下がります。</p>

          <h4 class="c-subheading">実装のポイント</h4>
          <dl class="p-points">
            <div><dt>PHP</dt><dd>フレームワークを使わない素のPHP。共通パーツの include とデータ駆動のページを、PHPサーバー版と静的HTML版の両方に書き出せる構成です。</dd></div>
            <div><dt>フォーム</dt><dd>CSRFトークン、セッションIDの再生成、送信後のリダイレクト（二重送信防止）、ハニーポットと入力時間による迷惑投稿対策、送信間隔の制限、メールヘッダへの改行混入対策を実装。自動返信メールには利用者の自由記述を含めません。</dd></div>
            <div><dt>広告表現の自動チェック</dt><dd>ビルドのたびに禁止表現、費用・リスク・ダウンタイムなどの必須表示、税込の総額表示を検査し、違反があればビルドを失敗させます。</dd></div>
            <div><dt>アニメーション</dt><dd>止まった状態を完成形として設計し、JavaScriptが動かない環境やスクリーンショットでも本文が欠けません。動きを減らす設定（prefers-reduced-motion）では演出を止めます。</dd></div>
            <div><dt>アクセシビリティ</dt><dd>キーボードだけで操作でき、メニュー・タブ・ダイアログのフォーカスを管理しています。文字と背景のコントラストは WCAG 2.1 AA を満たします。</dd></div>
            <div><dt>SEO</dt><dd>見出し階層とランドマークを整えたHTML、canonical・OGPの自動生成、構造化データ（MedicalClinic、監修者と最終確認日を持つ MedicalWebPage、BeautySalon、BreadcrumbList）。</dd></div>
            <div><dt>WordPress</dt><dd><?= e(($metrics['wordpress']['verified'] ?? false)
                ? '美容皮膚科サンプルのクラシックテーマ版を同梱。カスタム投稿タイプとメタボックスで施術情報を管理し、費用・リスクなどの必須表示が揃うまで公開できません（編集画面・REST API のどちらからでも）。WordPress ' . $metrics['wordpress']['version'] . '（SQLite構成）で、表示・予約フォーム・公開の制御を確認しています。'
                : '美容皮膚科サンプルのクラシックテーマ版を同梱しています。') ?></dd></div>
            <div><dt>デザイントークン</dt><dd>色・書体・余白を W3C Design Tokens 形式の JSON で同梱しています。本サンプルはデザインツールを介さずコードで制作しました。</dd></div>
          </dl>
          <p class="p-repo"><a class="c-button" href="<?= e(site('repository')) ?>">GitHub でソースコードを見る</a></p>
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="l-footer">
  <div class="l-wrap">
    <p>掲載しているサイト・ブランド・人物・住所・電話番号はすべて架空です。実在の医療機関・店舗・人物とは関係ありません。</p>
  </div>
</footer>
</body>
</html>
