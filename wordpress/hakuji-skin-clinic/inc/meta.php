<?php
/**
 * meta description・OGP・canonical（静的サイト版の seo_meta() に相当する最小限の出力）
 *
 * SEO プラグイン（Yoast SEO・All in One SEO・SEOPress など）を使う場合は二重出力を避けるため何も出さない。
 * 判定はフィルター hakuji_output_meta で上書きできる。
 *
 * @package hakuji
 */

declare(strict_types=1);

function hakuji_seo_plugin_active(): bool
{
    return defined('WPSEO_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('RANK_MATH_VERSION');
}

/** そのページの説明文（120文字程度） */
function hakuji_meta_description(): string
{
    $text = '';
    if (is_singular()) {
        $post = get_queried_object();
        $text = has_excerpt($post) ? get_the_excerpt($post) : wp_strip_all_tags(strip_shortcodes((string) $post->post_content));
    } elseif (is_post_type_archive('treatment')) {
        $text = hakuji_clinic('name') . 'の施術一覧です。施術ごとに、税込の費用・回数の目安・ダウンタイム・主なリスクと副作用を掲載しています。';
    } elseif (is_tax('concern')) {
        $text = single_term_title('', false) . 'の悩みに対応する施術の一覧です。税込の費用・回数の目安・主なリスクを掲載しています。';
    }
    if ($text === '') {
        $text = hakuji_clinic('name') . '（' . get_bloginfo('description') . '）。施術の前に、費用の総額・回数の目安・リスクと副作用をすべてご説明します。';
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    return mb_strlen($text) > 120 ? mb_substr($text, 0, 119) . '…' : $text;
}

/** canonical にする URL（ページ送りの2ページ目以降はそのページの URL） */
function hakuji_canonical_url(): string
{
    if (is_front_page()) {
        return home_url('/');
    }
    if (is_post_type_archive('treatment')) {
        return (string) get_post_type_archive_link('treatment');
    }
    if (is_tax('concern')) {
        $link = get_term_link(get_queried_object());
        return is_wp_error($link) ? '' : $link;
    }
    if (is_singular()) {
        return (string) wp_get_canonical_url();
    }
    return '';
}

add_action('wp_head', static function (): void {
    if (!apply_filters('hakuji_output_meta', !hakuji_seo_plugin_active()) || is_404()) {
        return;
    }
    $description = hakuji_meta_description();
    $url = hakuji_canonical_url();
    $tags = [['name', 'description', $description]];
    $tags[] = ['property', 'og:type', is_front_page() ? 'website' : 'article'];
    $tags[] = ['property', 'og:site_name', hakuji_clinic('name')];
    $tags[] = ['property', 'og:title', wp_get_document_title()];
    $tags[] = ['property', 'og:description', $description];
    $tags[] = ['property', 'og:locale', 'ja_JP'];
    if ($url !== '') {
        $tags[] = ['property', 'og:url', $url];
    }
    if (is_singular() && has_post_thumbnail()) {
        $tags[] = ['property', 'og:image', (string) get_the_post_thumbnail_url(null, 'large')];
    }
    foreach ($tags as [$attr, $key, $value]) {
        echo '<meta ' . $attr . '="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
    }
    // 投稿・固定ページは WordPress 本体が canonical を出力するため、それ以外のページだけ出す
    if ($url !== '' && !is_singular()) {
        echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    }
}, 5);
