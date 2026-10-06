<?php
/**
 * 構造化データ（JSON-LD）
 *
 * @package hakuji
 */

declare(strict_types=1);

function hakuji_business_ld(): array
{
    $c = hakuji_clinic();
    return [
        '@type' => 'MedicalClinic',
        '@id' => home_url('/#business'),
        'name' => $c['name'],
        'url' => home_url('/'),
        'telephone' => $c['tel_intl'],
        'email' => $c['email'],
        'medicalSpecialty' => 'Dermatology',
        'address' => [
            '@type' => 'PostalAddress',
            'postalCode' => $c['postal_code'],
            'addressRegion' => $c['region'],
            'addressLocality' => $c['locality'],
            'streetAddress' => $c['street'],
            'addressCountry' => 'JP',
        ],
        'openingHoursSpecification' => array_map(static fn ($h) => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $h['days'],
            'opens' => $h['opens'],
            'closes' => $h['closes'],
        ], $c['hours']),
    ];
}

add_action('wp_head', static function (): void {
    $graph = [];
    if (is_front_page()) {
        $graph[] = hakuji_business_ld();
    }
    $crumbs = hakuji_breadcrumbs();
    if (count($crumbs) > 1) {
        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(static fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $item['url'],
            ], $crumbs, array_keys($crumbs)),
        ];
    }
    if (is_singular('treatment')) {
        $post = get_queried_object();
        $reviewer = hakuji_clinic('reviewer');
        $graph[] = array_filter([
            '@type' => 'MedicalWebPage',
            'url' => get_permalink($post),
            'name' => get_the_title($post),
            'inLanguage' => 'ja',
            'lastReviewed' => get_post_meta($post->ID, '_hakuji_reviewed', true) ?: null,
            'reviewedBy' => ['@type' => 'Person', 'name' => $reviewer['name'], 'jobTitle' => $reviewer['title'], 'worksFor' => ['@id' => home_url('/#business')]],
            'about' => ['@type' => 'MedicalProcedure', 'name' => get_the_title($post)],
        ]);
    }
    if ($graph) {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], $flags) . "</script>\n";
    }
});
