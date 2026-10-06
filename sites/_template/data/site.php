<?php
/**
 * サイト設定（純粋な配列のみ。関数呼び出しや出力をしないこと）
 */

return [
    'name' => 'サンプルクリニック',
    'title_top' => 'サンプルクリニック｜雛形サイト',
    'description' => 'サイト雛形の説明文です。',
    'base' => '_template',
    'schema_type' => 'MedicalClinic',
    'medical_specialty' => 'Dermatology',
    'tel' => '03-0000-0000',
    'tel_intl' => '+81-3-0000-0000',
    'email' => 'info@example.com',
    'mail_from' => 'no-reply@example.com',
    'address' => [
        'postal_code' => '000-0000',
        'region' => '東京都',
        'locality' => '港区',
        'street' => '南青山0-0-0',
    ],
    'hours' => [
        ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Friday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00'],
    ],
    'theme_color' => '#ffffff',
    'og_image' => 'img/ogp.png',
    'nav' => [
        ['id' => 'index', 'label' => 'ホーム'],
    ],
];
