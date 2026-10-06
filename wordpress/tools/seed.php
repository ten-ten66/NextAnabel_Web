<?php
/**
 * ローカル確認用の初期データ投入（WP-CLI がない環境向け）
 *
 *   php wordpress/tools/seed.php /path/to/wordpress
 *
 * - テーマ「hakuji-skin-clinic」を有効化し、パーマリンクを /%postname%/ にする
 * - 静的サイト版の施術データ（sites/skin-clinic/data/treatments.php）を施術投稿として取り込む
 *   → 料金・リスクなどのデータを、静的サイト版と WordPress 版で共有できることの確認
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
$categories = [
    'spots' => 'シミ・くすみ',
    'firmness' => 'たるみ・ハリ',
    'pores' => '毛穴・ニキビ跡',
    'hair' => '医療脱毛',
];

switch_theme('hakuji-skin-clinic');
update_option('permalink_structure', '/%postname%/');
update_option('blogdescription', '南青山の美容皮膚科（架空のサンプル）');
update_option('timezone_string', 'Asia/Tokyo');
update_option('blog_public', '0');

// テーマの登録処理（カスタム投稿タイプ）を実行してから書き込む
do_action('init');
flush_rewrite_rules();

foreach ($categories as $slug => $name) {
    if (!term_exists($slug, 'concern')) {
        wp_insert_term($name, 'concern', ['slug' => $slug]);
    }
}

$lines = static fn (array $items): string => implode("\n", $items);
foreach (array_values($treatments) as $order => $t) {
    $existing = get_page_by_path($t['slug'], OBJECT, 'treatment');
    $content = '<p>' . esc_html($t['summary']) . '</p>';
    if (!empty($t['for'])) {
        $content .= '<h2>こんな方に</h2><ul><li>' . implode('</li><li>', array_map('esc_html', $t['for'])) . '</li></ul>';
    }
    if (!empty($t['flow'])) {
        $content .= '<h2>施術の流れ</h2><ol><li>' . implode('</li><li>', array_map('esc_html', array_map(
            static fn ($step) => is_array($step) ? implode('：', array_filter([$step['title'] ?? '', $step['text'] ?? ''])) : $step,
            $t['flow']
        ))) . '</li></ol>';
    }
    $postId = wp_insert_post([
        'ID' => $existing->ID ?? 0,
        'post_type' => 'treatment',
        'post_status' => 'draft',
        'post_title' => $t['name'],
        'post_name' => $t['slug'],
        'post_excerpt' => $t['summary'],
        'post_content' => $content,
        'menu_order' => $order,
    ], true);
    if (is_wp_error($postId)) {
        fwrite(STDERR, $t['slug'] . ': ' . $postId->get_error_message() . "\n");
        continue;
    }
    $meta = [
        '_hakuji_prices' => $lines(array_map(static fn ($p) => $p['label'] . '|' . $p['amount'], $t['prices'])),
        '_hakuji_courses' => $lines(array_map(static fn ($c) => $c['label'] . '|' . $c['months'] . '|' . $c['amount'], $t['courses'] ?? [])),
        '_hakuji_sessions' => $t['sessions'],
        '_hakuji_duration' => $t['duration'] ?? '',
        '_hakuji_downtime' => $t['downtime'],
        '_hakuji_risks' => $lines($t['risks']),
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
    if (isset($t['category'], $categories[$t['category']])) {
        wp_set_object_terms($postId, $t['category'], 'concern');
    }
    // 必須表示が揃っている施術だけを公開する（テーマの公開チェックと同じ条件）
    $missing = hakuji_missing_disclosures($postId);
    if (!$missing) {
        wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
    }
    printf("%-14s %s\n", $t['slug'], $missing ? '下書き（不足: ' . implode('、', $missing) . '）' : '公開');
}

$pages = [
    'contact' => ['予約・お問い合わせ', '<p>カウンセリング・施術のご予約は、以下のフォームからお申し込みください。</p>[hakuji_contact]'],
    'privacy' => ['プライバシーポリシー', '<p>当院は、ご予約・お問い合わせでお預かりした個人情報を、ご予約の受付とご連絡の目的にのみ利用します。症状などの健康に関する情報は、ご本人の同意のもとで取得し、診療予約の目的以外には利用しません。</p>'],
];
foreach ($pages as $slug => [$title, $content]) {
    $existing = get_page_by_path($slug);
    $id = wp_insert_post(['ID' => $existing->ID ?? 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content]);
    if ($slug === 'privacy' && !is_wp_error($id)) {
        update_option('wp_page_for_privacy_policy', $id);
    }
}
echo "完了\n";
