<?php
/**
 * ローカル確認用の初期データ投入（WP-CLI がない環境向け）
 *
 *   php wordpress/tools/seed.php /path/to/wordpress
 *
 * - テーマ「hakuji-skin-clinic」を有効化し、パーマリンクを /%postname%/ にする
 * - 静的サイト版の施術データ（sites/skin-clinic/data/treatments.php）を施術投稿として取り込む
 *   → 料金・リスクなどのデータを、静的サイト版と WordPress 版で共有できることの確認
 * - 静的サイト版のお知らせ（data/news.php）を投稿として取り込む
 * - 予約ページ（[hakuji_contact]）とプライバシーポリシーのページを作る
 */

declare(strict_types=1);

$wpDir = rtrim($argv[1] ?? '', '/');
if (!is_file($wpDir . '/wp-load.php')) {
    fwrite(STDERR, "使い方: php wordpress/tools/seed.php /path/to/wordpress\n");
    exit(1);
}
$_SERVER['HTTP_HOST'] ??= '127.0.0.1';
$_SERVER['REQUEST_URI'] ??= '/';
define('WP_USE_THEMES', false);
require $wpDir . '/wp-load.php';

$root = dirname(__DIR__, 2);
$treatments = require $root . '/sites/skin-clinic/data/treatments.php';
// 悩みカテゴリー（スラッグ => 名前）も静的サイト版のデータから作る
$categories = array_column(require $root . '/sites/skin-clinic/data/categories.php', 'label', 'id');

// テーマの functions.php は wp-load.php の時点で有効なテーマしか読み込まれないため、
// 切り替えた直後は同じスクリプトをもう一度実行し、テーマの登録処理（init）が通常どおり動いた状態で書き込む
if (get_stylesheet() !== 'hakuji-skin-clinic') {
    switch_theme('hakuji-skin-clinic');
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($wpDir), $code);
    exit($code);
}
if (!function_exists('hakuji_missing_disclosures')) {
    fwrite(STDERR, "テーマ hakuji-skin-clinic を読み込めませんでした。wp-content/themes に配置されているか確認してください。\n");
    exit(1);
}

update_option('permalink_structure', '/%postname%/');
update_option('blogdescription', '南青山の美容皮膚科（架空のサンプル）');
update_option('timezone_string', 'Asia/Tokyo');
update_option('blog_public', '0');
update_option('date_format', 'Y年n月j日');
flush_rewrite_rules();

foreach ($categories as $slug => $name) {
    if (!term_exists($slug, 'concern')) {
        wp_insert_term($name, 'concern', ['slug' => $slug]);
    }
}

$lines = static fn (array $items): string => implode("\n", $items);
foreach (array_values($treatments) as $order => $t) {
    $existing = get_page_by_path($t['slug'], OBJECT, 'treatment');
    $postId = wp_insert_post([
        'ID' => $existing->ID ?? 0,
        'post_type' => 'treatment',
        'post_status' => 'draft',
        'post_title' => $t['name'],
        'post_name' => $t['slug'],
        'post_excerpt' => $t['summary'],
        'post_content' => '',
        'menu_order' => $order,
    ], true);
    if (is_wp_error($postId)) {
        fwrite(STDERR, $t['slug'] . ': ' . $postId->get_error_message() . "\n");
        continue;
    }
    $meta = [
        '_hakuji_en' => $t['en'] ?? '',
        '_hakuji_lead' => $t['lead'] ?? '',
        '_hakuji_image' => basename((string) ($t['image'] ?? '')),
        '_hakuji_mirror' => !empty($t['mirror']) ? '1' : '',
        '_hakuji_for' => $lines($t['for'] ?? []),
        '_hakuji_flow' => $lines($t['flow'] ?? []),
        '_hakuji_prices' => $lines(array_map(static fn ($p) => $p['label'] . '|' . $p['amount'], $t['prices'])),
        '_hakuji_courses' => $lines(array_map(static fn ($c) => $c['label'] . '|' . $c['months'] . '|' . $c['amount'], $t['courses'] ?? [])),
        '_hakuji_price_note' => $t['price_note'] ?? '',
        '_hakuji_sessions' => $t['sessions'],
        '_hakuji_sessions_short' => $t['sessions_short'] ?? '',
        '_hakuji_duration' => $t['duration'] ?? '',
        '_hakuji_anesthesia' => $t['anesthesia'] ?? '',
        '_hakuji_downtime' => $t['downtime'],
        '_hakuji_downtime_short' => $t['downtime_short'] ?? '',
        '_hakuji_risks' => $lines($t['risks']),
        '_hakuji_risks_short' => $t['risks_short'] ?? '',
        '_hakuji_contraindications' => $lines($t['contraindications'] ?? []),
        '_hakuji_aftercare' => $lines($t['aftercare'] ?? []),
        '_hakuji_reviewed' => $t['reviewed'] ?? '',
        '_hakuji_unapproved' => !empty($t['unapproved']) ? '1' : '',
    ];
    foreach (HAKUJI_UNAPPROVED_ITEMS as $key => $label) {
        $meta['_hakuji_unapproved_' . $key] = (string) ($t['unapproved_info'][$key] ?? '');
    }
    foreach ($meta as $key => $value) {
        update_post_meta($postId, $key, $value);
    }
    // 対応する悩み（先頭が主な悩み）
    $terms = array_values(array_filter($t['categories'] ?? [$t['category']], static fn ($slug) => isset($categories[$slug])));
    wp_set_object_terms($postId, $terms, 'concern');
    // 必須表示が揃っている施術だけを公開する（テーマの公開チェックと同じ条件）
    $missing = hakuji_missing_disclosures($postId);
    if (!$missing) {
        wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
    }
    printf("%-14s %s\n", $t['slug'], $missing ? '下書き（不足: ' . implode('、', $missing) . '）' : '公開');
}

// お知らせ（静的サイト版の data/news.php と同じ内容）
$news = require $root . '/sites/skin-clinic/data/news.php';
foreach ($news as $item) {
    $categoryId = (int) (term_exists($item['category'], 'category')['term_id'] ?? wp_insert_term($item['category'], 'category')['term_id'] ?? 0);
    $existing = get_posts(['post_type' => 'post', 'title' => $item['title'], 'post_status' => 'any', 'numberposts' => 1]);
    wp_insert_post([
        'ID' => $existing[0]->ID ?? 0,
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => $item['title'],
        'post_content' => '<p>' . esc_html($item['body'] ?? $item['title']) . '</p>',
        'post_date' => $item['date'] . ' 10:00:00',
        'post_category' => $categoryId ? [$categoryId] : [],
    ]);
}
// WordPress の初期コンテンツ（Hello world!・サンプルページ・下書きのプライバシーポリシー）は使わない
foreach ([['hello-world', 'post'], ['sample-page', 'page'], ['privacy-policy', 'page']] as [$slug, $type]) {
    $default = get_page_by_path($slug, OBJECT, $type);
    if ($default && ($type !== 'page' || $default->post_status !== 'publish' || $slug === 'sample-page')) {
        wp_delete_post($default->ID, true);
    }
}

$staticPages = [
    'contact' => ['予約・お問い合わせ', '<p>カウンセリング・施術のご予約は、以下のフォームからお申し込みください。</p>[hakuji_contact]'],
    'privacy' => ['プライバシーポリシー', '<p>当院は、ご予約・お問い合わせでお預かりした個人情報を、ご予約の受付とご連絡の目的にのみ利用します。症状などの健康に関する情報は、ご本人の同意のもとで取得し、診療予約の目的以外には利用しません。</p>'],
];
foreach ($staticPages as $slug => [$title, $content]) {
    $existing = get_page_by_path($slug);
    $id = wp_insert_post(['ID' => $existing->ID ?? 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content]);
    if ($slug === 'privacy' && !is_wp_error($id)) {
        update_option('wp_page_for_privacy_policy', $id);
    }
}
echo "完了\n";
