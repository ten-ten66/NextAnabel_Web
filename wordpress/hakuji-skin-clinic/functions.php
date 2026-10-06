<?php
/**
 * 白磁スキンクリニック（サンプル）テーマ
 *
 * inc/post-types.php      施術（カスタム投稿タイプ）と悩みカテゴリー、メタ情報の登録
 * inc/meta-box.php        施術の必須表示を入力するメタボックスと、必須表示が揃うまで公開させない仕組み
 * inc/structured-data.php 構造化データ（MedicalClinic / MedicalWebPage / BreadcrumbList）
 * inc/contact-form.php    予約フォーム（ショートコード [hakuji_contact]）
 * inc/template-tags.php   テンプレート用の関数
 *
 * @package hakuji
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('HAKUJI_DIR', get_template_directory());
define('HAKUJI_URI', get_template_directory_uri());

require HAKUJI_DIR . '/inc/template-tags.php';
require HAKUJI_DIR . '/inc/post-types.php';
require HAKUJI_DIR . '/inc/meta-box.php';
require HAKUJI_DIR . '/inc/structured-data.php';
require HAKUJI_DIR . '/inc/contact-form.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);
    add_theme_support('responsive-embeds');
    register_nav_menus([
        'primary' => 'メインメニュー',
        'footer' => 'フッターメニュー',
    ]);
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'hakuji-fonts',
        'https://fonts.googleapis.com/css2?family=Shippori+Mincho+B1:wght@500&family=Zen+Kaku+Gothic+New:wght@400;700&display=swap',
        [],
        null
    );
    $css = HAKUJI_DIR . '/assets/css/style.css';
    wp_enqueue_style('hakuji', HAKUJI_URI . '/assets/css/style.css', ['hakuji-fonts'], (string) (is_file($css) ? filemtime($css) : '1'));
    $js = HAKUJI_DIR . '/assets/js/main.js';
    if (is_file($js)) {
        wp_enqueue_script('hakuji', HAKUJI_URI . '/assets/js/main.js', [], (string) filemtime($js), ['strategy' => 'defer', 'in_footer' => false]);
    }
});

// Google Fonts への事前接続
add_filter('wp_resource_hints', static function (array $urls, string $relation): array {
    if ($relation === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
    }
    return $urls;
}, 10, 2);

// タイトルの区切り文字を全角の縦線にする（静的サイト版と同じ表記）
add_filter('document_title_separator', static fn (): string => '｜');

// 架空サイトであることを全ページの先頭に表示する
add_action('wp_body_open', static function (): void {
    echo '<p class="c-demo-notice">' . esc_html__('このサイトはWeb制作のサンプルとして作成した架空のクリニックです。実在の医療機関・人物とは関係ありません。', 'hakuji') . '</p>';
});

// 不要な出力を減らす
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
