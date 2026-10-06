# オルヴァン美容外科（ORVANE AESTHETIC SURGERY）

銀座の美容外科を想定した、架空のクリニックのWebサイトサンプルです。実在の医療機関・人物とは関係ありません。

## コンセプト

**「墨と象牙」— できることと、できないことを、最初に伝える美容外科。**

美容外科の手術は、受けるかどうかをご自身で選ぶ医療で、元に戻すことが難しいものも少なくありません。そこで「カウンセリングで適応を見極める」「できないことも伝える」「術後まで伴走する」の3つを軸にし、費用の総額・リスク・経過を隠さず並べる誠実さを、落ち着いた高級感で表現しました。

- 墨色（#111418）の地に象牙色の文字。真鍮色（#C3A574）は細い罫線・数字・小さな印だけに使い、金のグラデーションや強い差し色は使っていません。
- 見出しは Zen Old Mincho、本文は IBM Plex Sans JP。ロゴタイプ「ORVANE」と大きな数字だけ、Bodoni Moda Italic を Google Fonts の `text=` 指定で必要な字形だけ読み込んでいます。
- 12カラムの非対称なグリッド、細い罫線、大きなイタリックの数字、縦書きの引用で、雑誌の誌面のような余白を作っています。角はすべて直角です。

## ページ構成

| ファイル | 静的版 | 内容 |
|---|---|---|
| `index.php` | `index.html` | ヒーロー、考え方、施術メニュー、施術の流れ、医師紹介、症例写真について、診療時間・アクセス、予約導線 |
| `treatment.php?slug=…` | `treatment-<slug>.html` | 施術詳細（埋没法・切開法・糸リフト・脂肪吸引の4ページ） |
| `cases.php` | `cases.html` | 症例写真の掲載方針と掲載テンプレート（写真は掲載せず枠のみ） |
| `doctor.php` | `doctor.html` | 院長プロフィール・診療方針・経歴 |
| `counseling.php` | `counseling.html` | カウンセリング予約（入力 → 確認 → 完了） |
| `privacy.php` | `privacy.html` | プライバシーポリシー |
| `404.php` | `404.html` | ページが見つからないとき |

## 見どころ（クリニックの方へ）

- **費用とリスクを同じ大きさで並べる**: 施術ページでは、税込の費用と主なリスク・副作用を左右に並べ、どちらも本文と同じ文字サイズで表示します。トップの施術カードにも、代表価格の隣にダウンタイムと主なリスクを載せています。
- **総額の誤解を防ぐ**: 麻酔・処方薬・術後検診など「料金に含まれるもの」と、静脈麻酔・カウンセリング料など「別途かかる費用」を分けて明記。修正・合併症の費用の扱いは書面で説明する方針も書いています。
- **カウンセリング当日に手術をしない**: 考える時間を持っていただく方針を、トップ・予約ページ・自動返信メールで一貫して伝えます。
- **症例写真の掲載テンプレート**: 写真の隣に治療内容・費用（税込）・主なリスク・経過期間・担当医を並べる型を用意。同意を得た写真のみ・加工しないという方針も明文化しています（「ビフォーアフター」ではなく「術前・術後」と表記）。
- **監修者と最終確認日を画面に表示**: 各施術ページの見出し直下と本文末尾に、監修医と最終確認日を表示しています。
- **予約フォームの自動返信に自由入力を含めない**: お名前やご相談内容を自動返信に載せないことで、第三者のアドレスを使った迷惑メールの踏み台にされるのを防ぎます。

## 見どころ（制作会社の方へ）

- **見出しの文字分割（GSAP SplitText）**: 読み込み時に文字が行の中から立ち上がります（1.2秒以内）。分割中は SplitText の `aria: "auto"` で見出しに `aria-label` を付けて各文字を読み上げ対象から外し、演出が終わると元のマークアップに戻します。JavaScript が動かない環境・動きを減らす設定では、最初から文字が表示されます。
- **スクロール連動**: ヒーロー画像の拡大・移動、画像枠内のパララックス、大きな数字のわずかなずれを ScrollTrigger の scrub で制御（transform のみ）。
- **施術の流れの横スクロール**: マークアップは縦の番号付きリストで、`gsap.matchMedia()` の `(min-width: 64em) and (prefers-reduced-motion: no-preference)` のときだけ、セクションを固定して横に進む演出に切り替えます。条件を外れると自動で元の縦並びに戻ります。
- **段階的強化**: 本文を透明のまま待たせる演出はありません。JavaScript なしでもすべての本文とナビゲーション（フッターへのメニューリンク）が使えます。
- **アクセシビリティ**: スキップリンク、ドロワーの `aria-expanded`/`aria-controls`・フォーカスの閉じ込め・Esc で閉じてトリガーへ戻す・背面を `inert` に、フォームのエラー一覧（各項目へのリンク）と `aria-describedby`/`aria-invalid`、`role="status"` の通知、44px 以上のタップ領域。
- **フォーム**: サーバー版は `Core\Form\FormFlow`（CSRF・PRG・ハニーポット・入力時間・送信間隔の制限）。静的版は同じマークアップのまま、JavaScript で確認・完了画面をデモ表示します（入力値は `textContent` でのみ出力し、送信はしません）。
- **構造化データ**: MedicalClinic（`business_ld()`）、施術ページの MedicalWebPage（`reviewedBy`・`lastReviewed`・`about` に MedicalProcedure / SurgicalProcedure）、医師紹介の ProfilePage、パンくず。
- **CSS 設計**: FLOCSS 系の接頭辞＋BEM、色・書体・余白・角丸は `tokens/surgery-clinic.tokens.json` と同じ値のカスタムプロパティ。ダークテーマ固定で、背景色・文字色をすべて明示しています。

## 技術メモ

```
sites/surgery-clinic/
├─ _init.php            初期化と、このサイト専用の小さなヘルパー（sc_ 接頭辞）
├─ build.config.php     静的ビルド設定（施術詳細は kind=treatment）
├─ data/site.php        名称・住所・電話・診療時間など（NAP はここだけで管理）
├─ data/treatments.php  施術データ（GUIDELINES 12章のスキーマ＋表示用の追加項目）
├─ data/cases.php       症例テンプレートのデータ（治療内容・費用・リスク・経過）
├─ data/doctor.php      院長プロフィール
├─ partials/            head / header（ドロワー含む）/ footer / page-head / cta / frame
├─ assets/css/style.css
├─ assets/js/main.js    自作スクリプト（約22KB・依存は assets/vendor/ の GSAP のみ）
├─ assets/img/          生成画像（WebP）、favicon.svg、ogp.png
├─ og.html              OGP 画像の原稿（node tools/og-image.mjs surgery-clinic）
└─ vendor.json          gsap / ScrollTrigger / SplitText
```

- 静的ビルド: `php tools/build.php --site=surgery-clinic --out=build/surgery-clinic`
- サーバー版の確認: `php -S 127.0.0.1:8202 -t sites/surgery-clinic` → `counseling.php`。メールは既定で `storage/mail.log` に書き出されます（`MAIL_TRANSPORT=native` で実際に送信）。
- 施術を追加するときは `data/treatments.php` に1件足すだけで、詳細ページ・トップのカード・フッター・ドロワー・予約フォームの選択肢に反映されます。
- 写真の差し替え: 「医師写真」「院内写真」は `partials/frame.php` の画像枠（4:5 / 3:2）です。撮影素材を `assets/img/` に置き、各ページの `partial('frame', …)` の `src` とキャプションを変更してください。症例写真は `cases.php` の `.p-case__frame` を `<img>` に置き換えます。
- OGP 画像は `og.html` を書き出したあと、256色に減色して 150KB 以下にしています（見た目の差はほぼありません）。
