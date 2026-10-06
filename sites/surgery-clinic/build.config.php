<?php
/**
 * 静的ビルドの設定（tools/build.php が読み込む）
 * 施術詳細ページは kind=treatment とし、表現チェックで必須表示（費用・リスク・監修など）を検査する。
 */

return [
    'base' => 'surgery-clinic',
    'profile' => 'medical',
    'compliance_data' => ['treatments' => 'data/treatments.php'],
    'pages' => static function (): array {
        $pages = [
            ['src' => 'index.php'],
            ['src' => 'cases.php'],
            ['src' => 'doctor.php'],
            ['src' => 'counseling.php'],
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
