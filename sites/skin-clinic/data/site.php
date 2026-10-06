<?php
/**
 * 白磁スキンクリニックの基本情報（架空）
 * 医療脱毛LP（sites/lp）もこのファイルを読み込み、名称・住所・電話番号（NAP）を一致させる。
 * 純粋な配列のみを返すこと。
 */

return [
    'name' => '白磁スキンクリニック',
    'name_en' => 'HAKUJI SKIN CLINIC',
    'title_top' => '白磁スキンクリニック｜南青山の美容皮膚科',
    'description' => '南青山の美容皮膚科「白磁スキンクリニック」。施術の前に、費用の総額・回数の目安・リスクと副作用をすべてご説明します。シミ・くすみ、たるみ、毛穴、医療脱毛のご相談はカウンセリングから。',
    'base' => 'skin-clinic',
    'schema_type' => 'MedicalClinic',
    'medical_specialty' => 'Dermatology',
    'price_range' => '¥4,400〜',
    'tel' => '03-0000-0000',
    'tel_intl' => '+81-3-0000-0000',
    'email' => 'info@example.com',
    'mail_from' => 'no-reply@example.com',
    'mail_to' => 'reservation@example.com',
    'address' => [
        'postal_code' => '000-0000',
        'region' => '東京都',
        'locality' => '港区',
        'street' => '南青山0-0-0 サンプルビル3階',
    ],
    'access' => [
        '東京メトロ「表参道」駅 A4出口から徒歩4分',
        '東京メトロ「外苑前」駅 1a出口から徒歩8分',
    ],
    // 構造化データ用（曜日は schema.org の DayOfWeek）
    'hours' => [
        ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Friday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00'],
        ['days' => ['Sunday'], 'opens' => '10:00', 'closes' => '17:00'],
    ],
    // 画面表示・受付状況の計算用（0=日曜 … 6=土曜）。最終受付は終了の30分前
    'schedule' => [
        0 => ['open' => '10:00', 'close' => '17:00'],
        1 => ['open' => '10:00', 'close' => '19:00'],
        2 => ['open' => '10:00', 'close' => '19:00'],
        3 => ['open' => '10:00', 'close' => '19:00'],
        4 => null,
        5 => ['open' => '10:00', 'close' => '19:00'],
        6 => ['open' => '10:00', 'close' => '19:00'],
    ],
    'last_entry_minutes' => 30,
    // 定休日（木曜）以外の休診日。祝日・年末年始を年に1回まとめて更新する（受付状況・本日の診療時間の表示に使う）
    'closed_dates' => [
        '2026-10-12', '2026-11-03', '2026-11-23',
        '2026-12-29', '2026-12-30', '2026-12-31', '2027-01-01', '2027-01-02', '2027-01-03',
        '2027-01-11', '2027-02-11', '2027-02-23', '2027-03-21', '2027-03-22', '2027-04-29',
        '2027-05-03', '2027-05-04', '2027-05-05', '2027-07-19', '2027-08-11',
        '2027-09-20', '2027-09-23', '2027-10-11', '2027-11-03', '2027-11-23',
    ],
    'closed_label' => '木曜・祝日',
    'reviewer' => ['name' => '汐見 透子', 'title' => '院長・皮膚科専門医'],
    'lp_url' => 'https://ten-ten66.github.io/NextAnabel_Web/lp/',
    'theme_color' => '#F7F8F6',
    'og_image' => 'img/ogp.png',
];
