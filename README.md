# 美容・医療 Webサイト サンプル集

美容皮膚科・美容外科・エステサロン・医療脱毛LPの、**架空**のサンプルサイト4点と、それらをまとめたサンプル集ページです。

> 掲載しているブランド・人物・住所・電話番号はすべて架空です。実在の医療機関・店舗・人物とは関係ありません。
> 公開版は全ページ `noindex` を付けており、検索結果には表示されません。

## 公開URL（GitHub Pages）

GitHub Pages を有効にすると、次の URL で公開されます（`master` にマージしたうえで Settings → Pages → Deploy from a branch → `master` / `/docs`）。

| ページ | URL |
|---|---|
| サンプル集 | https://ten-ten66.github.io/NextAnabel_Web/ |
| 白磁スキンクリニック（美容皮膚科） | https://ten-ten66.github.io/NextAnabel_Web/skin-clinic/ |
| オルヴァン美容外科 | https://ten-ten66.github.io/NextAnabel_Web/surgery-clinic/ |
| 苔と麻（エステサロン） | https://ten-ten66.github.io/NextAnabel_Web/esthetic/ |
| 白磁 医療脱毛LP | https://ten-ten66.github.io/NextAnabel_Web/lp/ |

## サンプル一覧

| サンプル | 種類 | 主な実装 |
|---|---|---|
| 白磁スキンクリニック | 美容皮膚科・コーポレートサイト | 素のPHP（共通パーツ・データ駆動の施術詳細・3段階の予約フォーム）、悩み別の絞り込み、受付状況の表示、WordPressテーマ版 |
| オルヴァン美容外科 | 美容外科・コーポレートサイト | GSAP（SplitText・ScrollTrigger）、症例写真の掲載テンプレート、カウンセリング予約フォーム |
| 苔と麻 | エステティックサロン | GSAP（ScrollTrigger・DrawSVG）、Swiper、医療的な効能をうたわない表現設計 |
| 白磁 医療脱毛LP | ランディングページ | 人体図と連動する料金シミュレーター、PHP の JSON API への非同期送信、dataLayer 計測 |

各サイトの詳細は `sites/<サイト>/README.md` を参照してください。

## 構成

```
core/        共通PHP（テンプレート関数、SEOメタ・構造化データ、フォーム部品）
sites/       各サイトのソース（PHP）。サーバーにそのまま置いても、静的HTMLに書き出しても動く
wordpress/   美容皮膚科サンプルの WordPress クラシックテーマ版と、確認用の初期データ投入スクリプト
tokens/      デザイントークン（W3C Design Tokens 形式の JSON）
tools/       ビルド、表現チェック、Webフォントのサブセット化、画像生成、品質チェック
tests/       表現チェックの回帰テスト、フォームの E2E テスト
docs/        静的ビルドの成果物（GitHub Pages の公開元。直接編集しない）
GUIDELINES.md  コーディング規約・品質基準・広告表現のルール
```

## 動かし方

必要なもの: PHP 8.1 以上（mbstring・curl）、Node.js 20 以上、Python 3（画像を作り直す場合のみ、Pillow・NumPy）

```bash
npm ci                     # 開発用ツールと GSAP・Swiper
npm run vendor             # ライブラリを各サイトの assets/vendor/ に複製
php tools/build.php        # docs/ に静的版を書き出し（表現チェック込み）
npm run serve:static       # http://127.0.0.1:8090/ で静的版を確認
npm run serve:php          # http://127.0.0.1:8080/skin-clinic/ でPHPサーバー版を確認（フォームが動く）
```

- PHPサーバー版のフォームは、既定では送信せずに `storage/mail.log` へ書き出します。実際に送信するには環境変数 `MAIL_TRANSPORT=native` を設定してください（送信元ドメインの SPF・DKIM・DMARC の設定を推奨）。
- `storage/` は公開ディレクトリの外に置いてください。

## 品質チェック

```bash
php tools/build.php                                   # ビルド＋広告表現チェック（違反でビルド失敗）
php tests/lint.test.php                               # 表現チェック自体の回帰テスト
npm run lint:php                                      # 全PHPの構文チェック
npm run lint:html                                     # HTMLの文法チェック（html-validate）
npm run test:forms                                    # フォームの E2E テスト（CSRF・二重送信・改行混入・XSS・休診日など）
npm run qa                                            # 全ページ×3画面幅：横スクロール、コンソールエラー、
                                                      # 読み込み直後・JS無効時に見えない本文、axe-core
php tools/build.php --env=production --out=build/production && node tools/qa/lighthouse.mjs
```

## 主な設計判断

- **広告表現の自動チェック**（`tools/lint-compliance.php`）: 医療広告に関するガイドラインで問題になりやすい表現（最上級、値引きの強調、体験談、リスクがないかのような表現など）と、自由診療の必須表示（費用・回数の目安・リスク・ダウンタイム・問い合わせ先・監修者）、税込の総額表示をビルドのたびに検査します。法令適合を保証するものではなく、制作時の見落としを機械的に減らす仕組みです。
- **アニメーションは「止まった状態が完成形」**: 本文を透明のまま待機させず、JavaScript が動かない環境やスクリーンショットでも内容が欠けないようにしています。`prefers-reduced-motion` では演出を止めます。
- **日本語Webフォントのサブセット化**（`tools/fonts.php`）: ビルド時にサイト内で使う文字だけのフォントを取得して同一オリジンから配信し、フォントの CSS は描画を止めないよう非同期に読み込みます（文字が多いサイトは使用頻度順に分割し、`unicode-range` で必要な分だけ読み込む）。
- **料金データの一元管理**: 医療脱毛の料金は `sites/skin-clinic/data/hair_removal.php` の1ファイルで管理し、コーポレートサイトとLPの両方が読み込みます。WordPress版も同じ施術データから初期投稿を作成できます。

## WordPress 版

`wordpress/hakuji-skin-clinic/` は美容皮膚科サンプルのクラシックテーマです。施術をカスタム投稿タイプで管理し、費用・リスク・ダウンタイムなどの必須表示が揃うまでは公開できません（下書きに戻して不足項目を通知。編集画面・クイック編集・REST API のどれから公開しても同じ）。WordPress 6.5.5 で表示・予約フォーム・公開の制御を確認しています（詳細と未確認の範囲は `wordpress/README.md`）。

```bash
# WordPress を用意したうえで
cp -r wordpress/hakuji-skin-clinic /path/to/wordpress/wp-content/themes/
php wordpress/tools/seed.php /path/to/wordpress   # テーマ有効化・施術とお知らせの投入
```

## デザイントークン

`tokens/*.tokens.json` に色・書体・余白・角丸を W3C Design Tokens 形式で収めています。Tokens Studio などのツールでデザインデータと値を揃える用途を想定しています。本サンプルはデザインツールを介さず、コードで制作しました。

## 使用しているもの

| 名称 | ライセンス |
|---|---|
| [GSAP](https://gsap.com/)（ScrollTrigger・SplitText・DrawSVG） | GSAP Standard "No Charge" License |
| [Swiper](https://swiperjs.com/) | MIT |
| Google Fonts（Shippori Mincho B1、Zen Kaku Gothic New、Zen Old Mincho、IBM Plex Sans JP、Bodoni Moda、Klee One、Zen Kaku Gothic Antique、Fraunces、Murecho、IBM Plex Mono） | SIL Open Font License 1.1 |
| WordPress（テーマの動作確認のみ。リポジトリには含みません） | GPL v2 以降 |

画像はすべて `tools/gen_visuals.py` で数式から生成したオリジナルです（写真素材は使用していません）。
