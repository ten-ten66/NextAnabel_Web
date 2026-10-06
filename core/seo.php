<?php
/**
 * SEO用のメタ情報と構造化データ
 */

declare(strict_types=1);

/**
 * JSON-LD を出力する。
 * 文字列中の "</script>" で script 要素が閉じられないよう、< > & ' " を \uXXXX にエスケープする。
 *
 * @param array<string, mixed> $data
 */
function json_ld(array $data): string
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
        | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;
    return '<script type="application/ld+json">' . json_encode($data, $flags) . '</script>';
}

/**
 * ページ設定から head 内のメタ情報を生成する。
 *
 * $page のキー
 *   id          ページ識別子。'index' はトップページ
 *   title       ページ名（トップでは site.title_top を使う）
 *   description 説明文（120字前後）
 *   type        og:type。省略時はトップが website、それ以外は article
 *   breadcrumb  [['label' => 'ホーム', 'href' => url('index')], ['label' => '施術一覧']]
 *   jsonld      追加の構造化データノードの配列（@graph に入る）
 *   image       OGP画像。assets/ からの相対パス
 *
 * @param array<string, mixed> $page
 */
function seo_meta(array $page): string
{
    $siteName = (string) site('name');
    $isTop = ($page['id'] ?? '') === 'index';
    $title = $isTop
        ? (string) site('title_top', $siteName)
        : sprintf('%s｜%s', (string) ($page['title'] ?? ''), $siteName);
    if (BUILD_ENV !== 'production' && site('demo_title_suffix', true)) {
        // 架空サイトであることを、タブ表示やSNSでの共有時にも分かるようにする
        $title .= '【サンプル】';
    }
    $description = (string) ($page['description'] ?? site('description', ''));
    $canonical = absolute_url();
    $image = absolute_url('assets/' . ltrim((string) ($page['image'] ?? site('og_image', 'img/ogp.png')), '/'));

    $lines = [
        '<title>' . e($title) . '</title>',
        '<meta name="description" content="' . e($description) . '">',
    ];
    if (BUILD_ENV !== 'production') {
        // 架空のサンプルサイトが検索結果に出ないようにする
        $lines[] = '<meta name="robots" content="noindex, nofollow">';
    }
    $lines[] = '<link rel="canonical" href="' . e($canonical) . '">';
    $lines[] = '<meta property="og:type" content="' . e($page['type'] ?? ($isTop ? 'website' : 'article')) . '">';
    $lines[] = '<meta property="og:title" content="' . e($title) . '">';
    $lines[] = '<meta property="og:description" content="' . e($description) . '">';
    $lines[] = '<meta property="og:url" content="' . e($canonical) . '">';
    $lines[] = '<meta property="og:site_name" content="' . e($siteName) . '">';
    $lines[] = '<meta property="og:image" content="' . e($image) . '">';
    $lines[] = '<meta property="og:locale" content="ja_JP">';
    $lines[] = '<meta name="twitter:card" content="summary_large_image">';
    if ($color = site('theme_color')) {
        $lines[] = '<meta name="theme-color" content="' . e($color) . '">';
    }

    $graph = [];
    if (!empty($page['breadcrumb'])) {
        $graph[] = breadcrumb_ld($page['breadcrumb']);
    }
    foreach ($page['jsonld'] ?? [] as $node) {
        $graph[] = $node;
    }
    if ($graph) {
        $lines[] = json_ld(['@context' => 'https://schema.org', '@graph' => $graph]);
    }

    return implode("\n", $lines) . "\n";
}

/**
 * パンくずの構造化データ
 *
 * @param list<array{label: string, href?: string}> $items
 * @return array<string, mixed>
 */
function breadcrumb_ld(array $items): array
{
    $list = [];
    foreach (array_values($items) as $i => $item) {
        $list[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['label'],
            'item' => absolute_url($item['href'] ?? null),
        ];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

/**
 * 事業所（クリニック・サロン）の構造化データ。値はサイト設定から組み立てる。
 *
 * @param array<string, mixed> $extra 追加・上書きするプロパティ
 * @return array<string, mixed>
 */
function business_ld(array $extra = []): array
{
    $address = (array) site('address', []);
    $hours = [];
    foreach ((array) site('hours', []) as $row) {
        $hours[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $row['days'],
            'opens' => $row['opens'],
            'closes' => $row['closes'],
        ];
    }
    $node = [
        '@type' => site('schema_type', 'LocalBusiness'),
        '@id' => absolute_url('') . '#business',
        'name' => site('name'),
        'url' => absolute_url(''),
        'image' => absolute_url('assets/' . ltrim((string) site('og_image', 'img/ogp.png'), '/')),
        'telephone' => site('tel_intl'),
        'email' => site('email'),
        'priceRange' => site('price_range'),
        'medicalSpecialty' => site('medical_specialty'),
        'address' => $address ? [
            '@type' => 'PostalAddress',
            'postalCode' => $address['postal_code'] ?? null,
            'addressRegion' => $address['region'] ?? null,
            'addressLocality' => $address['locality'] ?? null,
            'streetAddress' => $address['street'] ?? null,
            'addressCountry' => 'JP',
        ] : null,
        'openingHoursSpecification' => $hours ?: null,
    ];
    return array_filter(array_replace($node, $extra), static fn ($v) => $v !== null && $v !== []);
}

/**
 * 監修者（医師）の構造化データ。MedicalWebPage の reviewedBy に使う。
 *
 * @param array{name: string, title?: string} $person
 * @return array<string, mixed>
 */
function reviewer_ld(array $person): array
{
    return array_filter([
        '@type' => 'Person',
        'name' => $person['name'],
        'jobTitle' => $person['title'] ?? null,
        'worksFor' => ['@id' => absolute_url('') . '#business'],
    ], static fn ($v) => $v !== null);
}
