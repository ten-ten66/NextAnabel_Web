<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 * 施術詳細は data/treatments.php の slug ごとに treatment-<slug>.html を書き出す。
 */

return [
    'base' => 'skin-clinic',
    'profile' => 'medical',
    'compliance_data' => ['treatments' => 'data/treatments.php'],
    'pages' => static function (): array {
        $pages = [
            ['src' => 'index.php'],
            ['src' => 'treatments.php'],
            ['src' => 'price.php'],
            ['src' => 'doctor.php'],
            ['src' => 'clinic.php'],
            ['src' => 'faq.php'],
            ['src' => 'contact.php'],
            ['src' => 'privacy.php'],
            ['src' => '404.php'],
        ];
        foreach (require __DIR__ . '/data/treatments.php' as $treatment) {
            $pages[] = ['src' => 'treatment.php', 'query' => ['slug' => $treatment['slug']], 'kind' => 'treatment'];
        }
        return $pages;
    },
    'copy' => ['assets'],
];
