# 苔と麻（koke to asa）— エステティックサロンのサンプルサイト

自由が丘の路地にある、個室ふたつのフェイシャル・ボディトリートメントサロン「苔と麻」のサイトです。
**架空のサロン**で、店名・住所・電話番号・人物はすべて実在しません。医療機関ではありません。

## コンセプト

「静けさを、手のひらから。」

窓辺の植物の影が麻布に落ちる、午後の部屋をそのままサイトにしました。

- **色**：麻の生成り（linen）を地に、苔の濃い緑（moss）で文字を組み、差し色に藤色（mauve）を使います。クリーム×テラコッタ、ベージュ×金といったスパの定番配色は避けました。
- **書体**：見出しは教科書体のやわらかさを持つ Klee One、本文は Zen Kaku Gothic Antique。欧文のロゴと小さな見出しだけに、Fraunces のイタリックを使う文字だけ読み込んでいます。
- **形**：有機的な角丸（`--radius-organic`）の画像枠、植物の線画、麻の織り目のごく薄いテクスチャ。
- **季節**：二十四節気をふたつずつ組にした「香りのこよみ」を軸にしています。ファーストビューの「いまの香り」とこよみの帯の「いま」は、表示した日付（東京時間）で切り替わります。

## ページ構成

| ファイル | 内容 |
|---|---|
| `index.php` | トップ（ファーストビュー／はじめに／香りのこよみ／メニュー／施術の流れ／空間／セラピスト／はじめての方へ・よくある質問／アクセス・営業時間／ご予約） |
| `menu.php` | メニューと料金（カテゴリーごとの料金表、オプション、回数券・コース、クーリング・オフの案内、ご案内） |
| `404.php` | ページが見つからないとき |

データは `data/` に純粋な配列として分けています（`site.php` 基本情報・営業時間、`menu.php` 料金、`seasons.php` 香りのこよみ、`faq.php`、`staff.php`、`branch.php` ファーストビューの枝の座標）。

## 動きの設計

原則は「止まった状態が完成形」。本文をスクロール待ちで隠すことはしません。動きはすべて `prefers-reduced-motion: no-preference` のときだけ付け、動きを減らす設定では描き終えた状態で表示します。

1. **読み込みの演出（1.2秒以内）**
   店名が一文字ずつ立ち上がり、縦書きのキャッチ、リード文と続きます。この文字の表示は **CSS アニメーション**にしてあり、JavaScript の読み込みを待ちません（LCP を遅らせないため）。その間に、右上の枝の線画を **GSAP DrawSVGPlugin** で根元から描きます。葉は、茎が届く順に少しずつ遅れて描かれます（`data/branch.php` の `at`）。2.4秒たっても描画が始まらなければ、CSS がそのまま表示します。
2. **施術の流れの線画（スクロール連動）**
   4つのステップの番号を通る茎と葉を、**実際のレイアウトから SVG のパスとして組み立て**（画面幅が変わると `ResizeObserver` で組み直し）、スクロールに合わせて DrawSVGPlugin で描きます（ScrollTrigger の `scrub`）。葉と番号のまわりの輪は、茎がそこに届いたタイミングで描かれます。JavaScript なしでは簡易な茎、動きを減らす設定では葉まで描き終えた状態です。組み立ては画面に近づいてから行い、最初の表示の負担にしません。
3. **葉影の奥行き（パララックス）**
   ファーストビューは「麻布に落ちた葉影の写真」「枝の影（ぼかし）」「枝の線画」の3層。スクロールでそれぞれ違う速さで動きます。CSS のスクロール駆動アニメーション（`view-timeline` / `animation-timeline: view()`）を `@supports` の中で使い、未対応のブラウザだけ GSAP ScrollTrigger で同じ動きを付けます。画像枠は `overflow: clip`（スクロールコンテナを作らないため、`view()` が正しく効く）。
4. **磁石のようなボタン**
   主要な予約ボタンが、ポインターに少しだけ引き寄せられます（`gsap.quickTo`、`(hover: hover) and (pointer: fine)` のときだけ）。ファーストビューの葉影も、ポインターの位置で奥行きごとにわずかにずれます。
5. **香りのこよみ**
   ゆっくり流れる帯には一時停止ボタンを付け（WCAG 2.2.2）、画面外では止めます。速さは内容の幅から計算して一定（毎秒約26px）。JavaScript なし・動きを減らす設定では、折り返した一覧で全部を見せます。

GSAP の演出はすべて `gsap.matchMedia()` の中で作り、設定が変わったときに元へ戻します。動かすのは `transform` と `opacity`（線画は `stroke-dashoffset`）だけです。

## そのほかの JavaScript

- **ギャラリー（Swiper）**：画面に近づいたときに `assets/vendor/` から読み込みます。前後ボタン（端では `aria-disabled`）、ページ送り（`<button>`）、フォーカスがカルーセル内にあるときの ← → / Home / End、読み上げ用の状況表示（「5枚中2枚目：施術室」）。JavaScript なしでは横スクロールの一覧です。
- **ドロワー**：`aria-expanded` / `aria-controls`、フォーカスの閉じ込め、Esc で閉じてボタンへ戻す、背面は `inert`。
- **Web予約のダイアログ**：`<dialog>` の `showModal()`（背面は自動で操作不可）、Esc・背景クリックで閉じ、開いたボタンへフォーカスを戻します。開閉は `@starting-style` と `transition-behavior: allow-discrete` のトランジション。
- **よくある質問**：見出し＋`button` のアコーディオン。閉じた回答は `hidden="until-found"` にして、ページ内検索で見つかれば自動で開きます。JavaScript なしでは全部開いた状態です。
- **電話リンク**：デモ版では発信せず、`role="status"` の通知を出します。
- **営業状況**：東京時間で「ただいま営業中です（本日の最終受付 19:30）」などを表示します。
- **ページ遷移**：対応ブラウザでは View Transitions でゆっくり切り替わります。

## 表現のルールへの対応

- 医療的な効能（治療・改善など）、身体の変化の断定、医薬品的効能、脱毛・永久、最上級・最安・保証の表現は使いません。施術は「温めながらほぐします」「肌が整う心地よさ」「呼吸が深くなる時間」のように、**施術の内容と感じ方**で書いています。
- 「医療機関ではない」旨の注記のうち、「治療」という語が必要な1文だけ `data-lint-ignore` を付けています（メニューと料金の「ご案内」）。
- 料金はすべて税込。本文は `tax_in()`、料金表は `data-price-context="tax-included"` を付け、見出しに「（税込）」と書いています。
- 期間1か月超かつ5万円超のコースは、テンプレートで判定して `data-course="qualifying"` と「クーリング・オフの対象」の表示を付け、同じページに `data-disclosure="cooling-off"` の案内（8日以内の書面・電磁的記録による解除、8日経過後の中途解約と法令の上限内の解約手数料）を置いています。
- 口コミ・体験談は載せていません。「勧誘はしません」「コースはご希望の方にだけ書面でご案内」を明記しています。
- 妊娠中・持病のある方には、事前にかかりつけの医師への相談をお願いしています。

## 見どころ

### サロンのオーナーさまへ

- ファーストビューに、営業時間・定休日・駅からの時間・いまの香りがまとまっています。
- 料金表は「時間」と「料金（税込）」が一目で比べられ、回数券は1回あたりの料金も表示します。
- 予約は電話とWeb予約の2つ。Web予約は外部の予約システムに差し替える前提です。
- 季節ごとに香りの表示が変わるので、更新しなくても「いま」のサイトになります。
- 写真は撮影素材に差し替える前提の枠（角のトンボとキャプション付き）です。

### 制作会社の方へ

- 素の PHP（テンプレート＋データ配列）から静的 HTML を書き出す構成。
- GSAP（matchMedia / ScrollTrigger / DrawSVGPlugin / quickTo）、CSS のスクロール駆動アニメーション、Swiper の遅延読み込みを、段階的強化と動きを減らす設定の両立で組んでいます。
- 線画は手描きではなく、レイアウトからパスを生成しています（画面幅に追従）。
- JavaScript は依存なしの ES2020+、1ファイル約36KB（圧縮前）。CSS は FLOCSS＋BEM、色・余白・角丸はトークン（`tokens/esthetic.tokens.json`）と一致。

## ファイル構成

```
sites/esthetic/
├─ _init.php           初期化＋このサイトだけの小さなヘルパー（ナビのリンク先、コースの判定など）
├─ build.config.php    静的ビルドの設定（profile: esthetic）
├─ index.php / menu.php / 404.php
├─ partials/           head / header（ドロワー）/ footer / reserve（ご予約・ダイアログ）/ section-head
│                      logo / icons（SVG スプライト）/ hero-branch（枝の線画）/ map（イラスト地図）
├─ data/               site / menu / seasons / faq / staff / branch
├─ assets/css/style.css
├─ assets/js/main.js
├─ assets/img/         生成画像（WebP）・favicon.svg・ogp.png
├─ og.html             OGP 画像の元（node tools/og-image.mjs esthetic）
└─ vendor.json         gsap / ScrollTrigger / DrawSVGPlugin / swiper-bundle
```

## 確認の手順

```bash
node tools/vendor.mjs
php tools/build.php --site=esthetic --out=build/esthetic
npx html-validate "build/esthetic/esthetic/**/*.html"
node tools/qa/run.mjs --dir=build/esthetic --site=esthetic
```

## 補足

- 画像は `tools/gen_visuals.py` が生成した WebP です（`hero-leaves` / `leaves-room` / `leaves-mauve` / `stones` / `room`）。
- OGP 画像は、PNG の容量を抑えるため写真の質感を使わず、枝の線画と影だけで構成しています（1200×630・約140KB）。
- 和文フォントは Klee One（600）と Zen Kaku Gothic Antique（400・500）の3ウェイト。縦書きのキャッチも Klee One 600 にそろえ、1ウェイト分（約200KB）を減らしました。Fraunces は Google Fonts の `text=` で、ロゴと欧文の見出しに使う文字だけを読み込みます（`data/site.php` の `accent_glyphs`）。
- 表示速度のため、ファーストビューの文字は CSS で表示し（JavaScript を待たない）、Swiper はギャラリーが近づいてから、施術の流れの線画は画面に近づいてから組み立てます。
- ファーストビューの枝の座標（`data/branch.php`）は、葉の形と重力による垂れ方を計算する生成スクリプトで書き出し、手で整えたものです。
