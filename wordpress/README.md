# WordPress テーマ版（白磁スキンクリニック）

美容皮膚科サンプル `sites/skin-clinic` を、WordPress のクラシックテーマにしたものです。
デザインとマークアップは静的サイト版と同じで、**施術とお知らせを管理画面から更新できる**ようにしています。

## できること

| 機能 | 実装 |
|---|---|
| 施術の管理 | カスタム投稿タイプ「施術」（`/treatments/`）と、タクソノミー「お悩み」（`/concern/…/`） |
| 必須表示の入力 | 費用（税込）・回数の目安・ダウンタイム・主なリスク・禁忌・アフターケア・最終確認日をメタボックスで入力。未承認医薬品等を使う場合は5項目が必須になる |
| 公開ガード | 必須表示が揃っていない施術は、公開・予約投稿しようとしても下書きに戻し、不足項目を通知する。`wp_after_insert_post` で判定するため、編集画面・クイック編集・REST API のどれから公開しても同じ条件がかかる |
| コース契約の案内 | 「期間1か月超かつ5万円超」のコースを入力すると、クーリング・オフと中途解約の案内を自動で表示 |
| 構造化データ | MedicalClinic（トップ）・MedicalWebPage（施術。reviewedBy・lastReviewed）・BreadcrumbList |
| meta / OGP | description・OGP・canonical を出力。SEO プラグイン（Yoast SEO など）が有効なら出力しない |
| 予約フォーム | ショートコード `[hakuji_contact]`。`admin-post.php` で受け取り、nonce・ハニーポット・送信間隔（30秒）・休診日（木曜・祝日）の検査をして `wp_mail` で送信。PRG（303）。自動返信には自由記述を含めない |
| 受付状況・診療時間 | 静的サイト版と同じ JavaScript（`home.js`）で、本日の受付状況を1分ごとに更新 |

## ファイル構成

```
hakuji-skin-clinic/
├─ functions.php            読み込み・アセット登録（Webフォントは非同期読み込み）・head の整理
├─ inc/
│   ├─ post-types.php       施術・お悩みの登録、メタ情報の定義
│   ├─ meta-box.php         必須表示のメタボックスと公開ガード、一覧の「必須表示」列
│   ├─ structured-data.php  JSON-LD
│   ├─ meta.php             meta description・OGP・canonical
│   ├─ contact-form.php     予約フォーム
│   ├─ clinic.php           医院情報（名称・住所・診療時間・休診日）
│   └─ template-tags.php    テンプレート用の関数
├─ front-page.php / archive-treatment.php / taxonomy-concern.php / single-treatment.php / page.php / index.php / 404.php
├─ template-parts/          施術カード・料金表・絞り込み・受付状況・診療時間・地図・CTA など
└─ assets/                  css/style.css・js/*.js・img/* は静的サイト版の写し（下記）。css/theme.css だけが WordPress 専用
```

`assets/css/style.css`・`assets/js/main.js`・`assets/js/home.js`・`assets/img/*` は、静的サイト版から次のコマンドでコピーします（テーマ側では直接編集しない）。

```bash
php wordpress/tools/sync-assets.php
```

## ローカルでの確認手順

WordPress 6.5.5（`@wp-playground/wordpress-builds` に同梱のもの）と SQLite Database Integration、PHP 8.3 の `php -S` で確認しました。

```bash
# 1. WordPress を用意し、wp-content/themes/ にこのテーマを置く（シンボリックリンクでも可）
# 2. 初期データを投入する（テーマの有効化・パーマリンク・施術5件・お知らせ・予約ページ・プライバシーポリシー）
php wordpress/tools/seed.php /path/to/wordpress
# 3. 予約フォームの E2E テスト（WordPress の URL を渡す）
node tests/forms.e2e.mjs --wp=http://127.0.0.1:8300
# 4. 表示の検査（375 / 768 / 1440px、JavaScript 無効、axe）と HTML の検証
node tools/qa/run.mjs --url=http://127.0.0.1:8300 --paths=/,/treatments/,/treatments/hifu/,/contact/ --save-html=qa-output/wp-html
npx html-validate "qa-output/wp-html/*.html"
```

`seed.php` は施術データを静的サイト版の `sites/skin-clinic/data/treatments.php` から取り込みます。料金やリスクの記載を、静的サイト版と WordPress 版で同じデータから作れることの確認を兼ねています。

## 確認済みの範囲

- PHP の警告・エラー: 0件（`WP_DEBUG_LOG`）
- 表示: トップ・施術一覧・お悩み別一覧・施術詳細・予約・プライバシーポリシー・404 で、横スクロールなし・コンソールエラーなし・axe の serious / critical 0件・JavaScript 無効時も本文をすべて表示
- 公開ガード: `wp_insert_post`・予約投稿・REST API（作成・更新）・管理画面の編集画面の各経路で、必須表示が欠けた施術が下書きに戻ること
- 予約フォーム: nonce・ハニーポット・入力エラー（カナ・メール形式・改行混入・休診日・同意）・PRG
- HTML: html-validate で WordPress 本体が出力する属性の引用符（シングルクォート）以外のエラーなし

## 未確認の範囲

- 実際のメール送信（確認環境では `wp_mail` をログ出力に置き換えた。本番では SMTP の設定またはプラグインが必要）
- WordPress 6.6 以降、PHP 8.1 / 8.2、マルチサイト、ブロックエディターで作成した固定ページの見た目
- 日本語の言語パック（確認環境は英語版の WordPress。テーマの文言はすべて日本語で、`lang="ja"` を出力する）
