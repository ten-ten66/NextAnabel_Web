<?php
/**
 * 白磁スキンクリニック（サンプル）テーマ
 *
 * inc/post-types.php      施術（カスタム投稿タイプ）と悩みカテゴリー、メタ情報の登録
 * inc/meta-box.php        施術の必須表示を入力するメタボックスと、必須表示が揃うまで公開させない仕組み
 * inc/structured-data.php 構造化データ（MedicalClinic / MedicalWebPage / BreadcrumbList）
 * inc/meta.php            meta description・OGP・canonical（SEO プラグインがある場合は出力しない）
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
require HAKUJI_DIR . '/inc/meta.php';
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
    $theme = HAKUJI_DIR . '/assets/css/theme.css';
    wp_enqueue_style('hakuji-theme', HAKUJI_URI . '/assets/css/theme.css', ['hakuji'], (string) (is_file($theme) ? filemtime($theme) : '1'));
    $js = HAKUJI_DIR . '/assets/js/main.js';
    if (is_file($js)) {
        wp_enqueue_script('hakuji', HAKUJI_URI . '/assets/js/main.js', [], (string) filemtime($js), ['strategy' => 'defer', 'in_footer' => false]);
    }
    // トップページだけで使う演出（背景の Canvas・受付状況）
    $home = HAKUJI_DIR . '/assets/js/home.js';
    if (is_front_page() && is_file($home)) {
        wp_enqueue_script('hakuji-home', HAKUJI_URI . '/assets/js/home.js', ['hakuji'], (string) filemtime($home), ['strategy' => 'defer', 'in_footer' => false]);
    }
});

// Webフォントの CSS は、最初の描画（FCP）が画面に表示されてから読み込む（静的サイト版 tools/fonts.php の after-paint と同じ方式）。
// 日本語フォントは数百KBになり、先に読むとファーストビューの画像と回線を奪い合うため。
// 描画の計測に対応しないブラウザでは load 後に、いずれの場合も3秒後には読み込む。JavaScript が無効なら noscript で読み込む
add_filter('style_loader_tag', static function (string $html, string $handle, string $href): string {
    if ($handle !== 'hakuji-fonts') {
        return $html;
    }
    $url = html_entity_decode($href, ENT_QUOTES | ENT_HTML5); // WordPress から渡る URL は & が &#038; になっている
    $loader = "(function(h){var d=0,l=function(){if(d)return;d=1;var k=document.createElement('link');k.rel='stylesheet';k.href=h;document.head.appendChild(k)};"
        . "try{if(PerformanceObserver.supportedEntryTypes.indexOf('paint')<0)throw 0;"
        . "new PerformanceObserver(function(s){if(s.getEntriesByName('first-contentful-paint').length)setTimeout(l,0)}).observe({type:'paint',buffered:true})}"
        . "catch(e){addEventListener('load',l)}setTimeout(l,3000)})(" . wp_json_encode(esc_url_raw($url)) . ')';
    return '<script>' . $loader . "</script>\n"
        . '<noscript><link rel="stylesheet" href="' . esc_url($href) . '"></noscript>' . "\n";
}, 10, 3);

// Google Fonts への事前接続
add_filter('wp_resource_hints', static function (array $urls, string $relation): array {
    if ($relation === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin'];
    }
    return $urls;
}, 10, 2);

// タイトルの区切り文字を全角の縦線にし、前後の空白を詰める（静的サイト版と同じ「施術名｜医院名」の表記）
add_filter('document_title_separator', static fn (): string => '｜');
add_filter('document_title', static fn (string $title): string => str_replace(' ｜ ', '｜', $title));

// テーマの文言はすべて日本語のため、管理画面の言語設定にかかわらず文書の言語を日本語にする
add_filter('language_attributes', static fn (string $output): string => (string) preg_replace('/\blang="[^"]*"/', 'lang="ja"', $output));

// ブロックを使っていないページでは、ブロック用の CSS を読み込まない（クラシックテーマのため）
add_action('wp_enqueue_scripts', static function (): void {
    if (is_singular() && has_blocks()) {
        return;
    }
    foreach (['wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles'] as $handle) {
        wp_dequeue_style($handle);
    }
}, 100);

// 架空サイトであることを全ページの先頭に表示する（静的サイト版と同じマークアップ）
add_action('wp_body_open', static function (): void {
    echo '<aside class="c-demo-notice" aria-label="このサイトについて"><p class="c-demo-notice__text">'
        . esc_html__('このサイトはWeb制作のサンプルとして作成した架空のクリニックです。実在の医療機関・人物とは関係ありません。', 'hakuji')
        . '</p></aside>';
});

// JavaScript が無効なときは、JavaScript でしか動かない部品（悩み別の絞り込みなど）を隠す
add_action('wp_head', static function (): void {
    echo '<noscript><style>.u-js-only{display:none!important}</style></noscript>' . "\n";
}, 20);

// 不要な出力を減らす
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
