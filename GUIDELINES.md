# 制作ガイドライン

このリポジトリの全サンプルサイトが従う規約です。コーディング規約・品質基準・広告表現のルールをまとめています。

## 1. 前提

- すべて**架空のサンプル**です。ブランド名・医師名・住所・電話番号は実在しません。
- 公開版（demo）は全ページ `noindex`。`seo_meta()` が自動で付与します。
- 全ページの先頭に架空である旨の注記を表示します（`data-lint-ignore` を付けて表現チェックの対象外にする）。
- 「法令に適合することを保証する」とは書きません。表記は「ガイドラインを踏まえた設計」とします。

### 架空データのルール

| 項目 | 値 |
|---|---|
| 電話番号 | `03-0000-000X`（市内局番が0で始まる番号は割り当てられない） |
| メール | `info@example.com`（RFC 2606 の予約ドメイン） |
| 郵便番号 | `000-0000` |
| 番地 | `0-0-0`（存在しない番地） |
| 人名 | 実在の医師と重複しないことを検索で確認したもの |
| 製品名 | 商標（例: 特定メーカーの機器名・薬剤名）を使わず一般名称で書く |

## 2. ディレクトリ構成（サイト単位）

```
sites/<site>/
├─ _init.php           各ページの先頭で require する初期化
├─ build.config.php    静的ビルドの設定
├─ index.php …         ページ（1ページ = 1ファイル、フラットな1階層）
├─ partials/           head / header / footer などの共通パーツ
├─ data/               純粋な配列だけを返すデータファイル（site.php ほか）
├─ assets/
│   ├─ css/style.css
│   ├─ js/main.js
│   ├─ img/            生成画像（WebP）・SVG
│   └─ vendor/         npm から複製したライブラリ（tools/vendor.mjs）
├─ vendor.json         assets/vendor/ に複製するファイルの一覧
└─ README.md           サイトのコンセプト・ページ構成・見どころ
```

`sites/_template/` が最小構成の雛形です。

## 3. PHP テンプレート

```php
<?php
require __DIR__ . '/_init.php';

$page = [
    'id' => 'treatments',                 // ページ識別子（ナビの現在地表示に使う）
    'title' => '施術一覧',                 // <title> は「施術一覧｜サイト名」になる
    'description' => '…（120字前後）',
    'breadcrumb' => [
        ['label' => 'ホーム', 'href' => url('index')],
        ['label' => '施術一覧'],
    ],
    'jsonld' => [ /* 追加の構造化データ */ ],
];
partial('head', compact('page'));
partial('header', compact('page'));
?>
<main id="main"> … </main>
<?php partial('footer'); ?>
```

- 出力は必ず `e()` を通す。HTML を返すヘルパー（`time_tag()` など）だけは例外。
- リンクは `url('page')` / `url('treatment', ['slug' => 'hifu'])`、静的ファイルは `asset('css/style.css')`。直書きしない。
- 料金は `tax_in(22000)`（→「22,000円（税込）」）で出す。表で単位を見出しにまとめる場合は、表に `data-price-context="tax-included"` を付け、見出しに「（税込）」と書く。
- データファイルは配列を `return` するだけにする（関数呼び出し・出力・副作用なし）。表現チェックが直接読み込むため。
- フォームの `FormFlow::handle()` は出力より前に呼ぶ（リダイレクトのため）。
- 実行モードは `is_static()`、公開環境は `BUILD_ENV`（demo / production）で判定する。

## 4. 静的ビルドと公開先

- `php tools/build.php` で `docs/` に書き出す。サーバー版の `treatment.php?slug=hifu` は静的版で `treatment-hifu.html` になる。
- サイト内リンクはすべて相対パス（フラット構成なので `../` は不要）。ルート相対（`/`始まり）は GitHub Pages のサブパスで壊れるため禁止。
- 絶対URLは canonical と OGP のみ（`SITE_ORIGIN` から生成）。
- `<html>` の `data-env` が `static` のとき、フォームは送信せずデモ表示にする。`data-build` が `demo` のときは `tel:` リンクを押しても発信せず通知を出す（5章のスニペット）。
- 公開先は GitHub Pages と claude.ai Artifact。Artifact は外部の画像・CSS・iframe を読み込めないため、Google Fonts 以外の外部リソースを使わない。地図は埋め込まず、イラスト＋地図アプリへのリンクにする。

## 5. HTML

- `lang="ja"`、ランドマーク（`header` / `nav` / `main` / `footer`）、h1 は1ページに1つ。見出しレベルを飛ばさない。
- 本文へのスキップリンク、`aria-current="page"`、パンくずは `nav aria-label="パンくずリスト"` ＋ `ol`。
- 画像は `width` / `height`（または `aspect-ratio`）を必ず指定し、CLS を出さない。ファーストビューの主要画像は `fetchpriority="high"`、それ以外は `loading="lazy" decoding="async"`。装飾画像は `alt=""`。
- 表は `caption` と `th scope`、日付は `<time datetime>`、所在地は `<address>`。
- ボタンとリンクを使い分ける（ページ遷移は `a`、操作は `button`）。
- フォームは `label` 必須、`autocomplete` / `inputmode` / `type` を適切に指定し、エラーは `aria-describedby` と `aria-invalid` で関連付ける。
- `tel:` リンクの通知（demo のみ）:

```js
if (document.documentElement.dataset.build === 'demo') {
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="tel:"]');
    if (!link) return;
    event.preventDefault();
    showToast('サンプルサイトのため、電話はかかりません。'); // role="status" の領域に表示
  });
}
```

## 6. CSS

- 命名は FLOCSS 系のレイヤー接頭辞＋BEM。`l-`（レイアウト）、`c-`（汎用部品）、`p-`（ページ固有）、`u-`（ユーティリティ）、`is-`（状態）、`js-`（JavaScript のフック。スタイルを当てない）。
- 色・余白・書体・角丸・影は `:root` のカスタムプロパティ（トークン）で定義し、直書きしない。トークンは `tokens/<site>.tokens.json` と一致させる。
- モバイルファースト。ブレークポイントは `48em`（768px）、`64em`（1024px）、`80em`（1280px）。
- 文字サイズは `clamp()` で流体的に変える。本文は 16px 以上、行間 1.8 前後、1行は全角35〜40字程度。
- `!important` はユーティリティ以外で使わない。
- フォーカスは `:focus-visible` で必ず見えるようにする。
- フォントは Google Fonts から最大2ファミリー・合計4ウェイト以内。`display=swap`。ロゴや見出しの数文字だけに使う書体は、`text=` で使う文字を指定すれば3つ目のファミリーとして追加してよい。
- テンプレートには Google Fonts の `<link>` を書くだけでよい。ビルド時に `tools/fonts.php` がサイト内で使う文字だけのサブセットを取得して同一オリジンに置き、表示を止めないよう非同期で読み込む（本文はいったん端末のフォントで表示し、届いた時点で差し替える）。`text=` 付きの `<link>` はその文字だけで取得する。Artifact 用のビルドでは Google Fonts の読み込みのまま残す。
- サンプルサイトはサイトごとに意図したテーマで固定する（美容外科はダーク、その他はライト）。背景色と文字色を必ず明示する。

## 7. JavaScript

- 依存なしの ES2020+（GSAP・Swiper を使うサイトのみ `assets/vendor/` から読み込む）。`defer` で読み込み、インラインのイベントハンドラは使わない（`tools/fonts.php` が出力するフォント CSS の非同期読み込みのみ例外）。
- ファーストビューに関係しない重いライブラリ（ギャラリーの Swiper など）は、対象が画面に近づいてから読み込んでよい。
- 表示を変えない初期化（計測・監視・演出の準備）は、最初の描画のあと（`requestAnimationFrame` の中の `setTimeout`）に行う。読み込み直後に実行すると、低速な端末で最初の表示が遅れる。
- **JavaScript が動かなくても、すべての本文が読めること**（段階的強化）。
- スクロール監視は `IntersectionObserver`、描画は `requestAnimationFrame`、`scroll` / `touchmove` リスナーは `{ passive: true }`。
- コンソールエラー 0（静的版を含む）。静的版で fetch を呼ばない。
- フォーム送信のデモ表示では、ユーザー入力を `textContent` で出力する（`innerHTML` に入れない）。

## 8. アニメーション

原則: **止まった状態が完成形**。本文を透明・画面外のまま待機させない。

| 使う | 使わない |
|---|---|
| 読み込み時の短い演出（1.2秒以内に完了） | 本文を `opacity: 0` にしてスクロールで出す |
| スクロール位置に連動した変形（パララックス、線画の描画、拡大） | ビューポートの高さに合わせた巨大なファーストビュー（`100vh`） |
| 操作に応じた演出（並べ替え、タブ、アコーディオン、シミュレーター） | スクロールの乗っ取り（慣性スクロールの強制） |
| ページ遷移アニメーション（View Transitions、対応ブラウザのみ） | 自動で動き続け、止められない要素 |

- 動かすのは `transform` と `opacity` のみ。`will-change` は動作中だけ付ける。
- ファーストビューの最大の要素（LCP になる画像や見出し）は透明から始めない。読み込み時の演出は位置や大きさの変化にとどめる。
- `prefers-reduced-motion: reduce` では演出を止める（View Transitions も無効化）。
- 画面外の Canvas は描画を止める（`IntersectionObserver` と `visibilitychange`）。
- GSAP を使う場合は `gsap.matchMedia()` で「PC かつ動きを減らす設定でない」ときだけ複雑な演出を有効にし、それ以外は通常の縦並びにする。
- 5秒を超えて動き続ける要素（ループする文字など）には一時停止ボタンを付ける。

## 9. アクセシビリティ

- コントラスト比 4.5:1 以上（大きな文字は 3:1）。
- タップ領域は 44×44px 以上。
- ドロワーメニューは `aria-expanded` / `aria-controls`、開いている間はフォーカスを閉じ込め、Esc で閉じてトリガーにフォーカスを戻す。
- タブは WAI-ARIA のタブパターン（矢印キーで移動）、アコーディオンは `button` ＋ `aria-expanded`（または `details`）。
- 料金シミュレーターなど動的に変わる合計は、確定値だけを `aria-live="polite"` で読み上げる。
- キーボードだけで全操作ができること。

## 10. SEO

- `seo_meta($page)` が title / description / canonical / OGP / robots / JSON-LD を出力する。
- 構造化データ: 事業所は `business_ld()`（MedicalClinic / BeautySalon）、施術ページは `MedicalWebPage`（`reviewedBy` に `reviewer_ld()`、`lastReviewed`）、下層ページは `breadcrumb` から BreadcrumbList が自動で付く。
- FAQ ページは見出しと回答のマークアップを正しく書く（FAQ のリッチリザルトは Google が提供を終了しているため、効果はうたわない）。
- 医療は YMYL 領域。監修医名・最終確認日を**画面上にも**表示する。
- 内部リンク: 施術一覧 ⇄ 詳細 ⇄ 料金 ⇄ 予約 を相互に結ぶ。

## 11. 広告表現のルール

`tools/lint-compliance.php` がビルドのたびに機械的に検査します（違反があるとビルド失敗）。

### 医療機関（profile=medical）

- 書かない: 最上級・No.1、最安、最新・最先端の強調、永久、効果の保証、キャンペーン・割引・月額・し放題、体験談・口コミ・満足度、著名人の利用、「痛くない」「ダウンタイムなし」などリスクがないかのような表現、「ビフォーアフター」（「症例写真」と書く）。
- 施術詳細ページ（build.config の `kind => 'treatment'`）に必須の表示。いずれも `details` の中に入れない:

| data 属性 | 内容 |
|---|---|
| `data-disclosure="price"` | 費用（税込） |
| `data-disclosure="sessions"` | 回数・期間の目安（個人差の注記） |
| `data-disclosure="risks"` | 主なリスク・副作用 |
| `data-disclosure="downtime"` | ダウンタイム |
| `data-disclosure="contact"` | 問い合わせ先 |
| `data-reviewed-by` | 監修医・最終確認日 |

- `price` と `risks` は同じ `data-disclosure-group` の中に並べ、リスクを本文と同じ文字サイズで価格の隣に出す。
- 期間1か月超かつ5万円超のコースは特定継続的役務に当たる（美容医療も対象）。該当コースの要素に `data-course="qualifying"`、同じページに `data-disclosure="cooling-off"` の案内を置く。金額の基準は「5万円」と書く（「50,000円」と書くと価格として検査される）。
- 症例写真の枠（`data-case`）には、同じ枠内に `data-disclosure="treatment"`・`"price"`・`"risks"` の説明を置く。
- 未承認医薬品・医療機器を使う施術（データで `unapproved => true`）は `data-unapproved` の枠に5項目（`data-unapproved-item="status" / "route" / "domestic" / "overseas" / "relief"`）を表示する。

### エステ（profile=esthetic）

- 書かない: 治療・治る・改善などの医療的表現、脂肪・セルライト・小顔・痩身などの身体の変化の断定、シミ・シワが消えるなどの医薬品的効能、脱毛・永久、最上級、最安、効果の保証。
- コース契約（期間1か月超かつ5万円超）はクーリング・オフの案内を置く。

### 共通

- 価格はすべて総額（税込）。月額だけを強調しない。
- 口コミ・体験談・インフルエンサー投稿を載せない（ステルスマーケティング規制の観点でも扱わない）。

## 12. 施術データのスキーマ（医療機関）

```php
return [
    [
        'slug' => 'hifu',                       // 詳細ページは treatment-hifu.html
        'name' => 'HIFU（高密度焦点式超音波）',
        'category' => 'firmness',               // 悩み別フィルタのキー
        'summary' => '…',
        'for' => ['…'],                         // こんな方に
        'flow' => ['…'],                        // 施術の流れ
        'prices' => [['label' => '全顔 1回', 'amount' => 55000]],   // 税込の整数
        'courses' => [['label' => '全顔 3回', 'months' => 9, 'amount' => 148500]],
        'sessions' => '…',                      // 回数・期間の目安（個人差の注記を含める）
        'duration' => '約40分',
        'downtime' => '…',
        'risks' => ['…'],
        'contraindications' => ['…'],           // 受けられない方
        'aftercare' => ['…'],
        'unapproved' => false,
        'unapproved_info' => [],                // unapproved が true のとき5項目
        'reviewed' => '2026-09-30',
    ],
];
```

## 13. 画像

- 写真は使わない。`tools/gen_visuals.py` が生成した WebP と SVG を使う。
- 写真が入る想定の箇所は「差し替え前提の画像枠」として、比率とキャプション（例:「院内写真（撮影素材に差し替え）」）を持つ部品にする。
- OGP 画像は 1200×630。

## 14. パフォーマンス予算

| 指標 | 上限 |
|---|---|
| 自作 JavaScript（1ページ、圧縮前） | 40KB |
| 画像1枚 | 200KB（ファーストビューは 150KB） |
| Webフォント | 2ファミリー・4ウェイト |
| CLS | 0.05 未満 |

## 15. 品質ゲート

```bash
npm run vendor                       # ライブラリを assets/vendor/ に複製
php tools/build.php                  # ビルド＋表現チェック（違反でエラー）
npm run lint:php                     # 全 PHP の構文チェック
npx html-validate "docs/<site>/**/*.html"
npm run qa                           # スクリーンショット・横スクロール・コンソールエラー・axe・Lighthouse
```

すべてエラー 0 で完了とします。
