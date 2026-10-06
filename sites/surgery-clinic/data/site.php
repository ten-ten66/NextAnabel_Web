<?php
/**
 * オルヴァン美容外科の基本情報（架空）
 * 名称・住所・電話番号（NAP）はこのファイルだけで管理し、各ページ・構造化データ・メールで共通に使う。
 * 純粋な配列のみを返すこと。
 */

return [
    'name' => 'オルヴァン美容外科',
    'name_en' => 'ORVANE AESTHETIC SURGERY',
    'title_top' => 'オルヴァン美容外科｜銀座の美容外科',
    'description' => '銀座の美容外科「オルヴァン美容外科」。形成外科専門医の院長が、二重まぶた・糸によるリフトアップ・脂肪吸引のカウンセリングから術後の検診まで担当します。費用の総額とリスク・副作用は、施術の前にすべてご説明します。',
    'base' => 'surgery-clinic',
    'schema_type' => 'MedicalClinic',
    'medical_specialty' => 'PlasticSurgery',
    'price_range' => '¥99,000〜',
    'tel' => '03-0000-0001',
    'tel_intl' => '+81-3-0000-0001',
    'email' => 'info@example.com',
    'mail_from' => 'no-reply@example.com',
    'mail_to' => 'counseling@example.com',
    'address' => [
        'postal_code' => '000-0000',
        'region' => '東京都',
        'locality' => '中央区',
        'street' => '銀座0-0-0 サンプルタワー8階',
    ],
    'access' => [
        '東京メトロ「銀座」駅 A2出口から徒歩5分',
        '東京メトロ「銀座一丁目」駅から徒歩6分',
    ],
    // 構造化データ用（曜日は schema.org の DayOfWeek）
    'hours' => [
        ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00'],
    ],
    // 画面表示用（0=日曜 … 6=土曜）。最終受付は終了の60分前
    'schedule' => [
        0 => null,
        1 => ['open' => '10:00', 'close' => '19:00'],
        2 => ['open' => '10:00', 'close' => '19:00'],
        3 => ['open' => '10:00', 'close' => '19:00'],
        4 => ['open' => '10:00', 'close' => '19:00'],
        5 => ['open' => '10:00', 'close' => '19:00'],
        6 => ['open' => '10:00', 'close' => '19:00'],
    ],
    'hours_label' => '月〜土 10:00–19:00',
    'last_entry_minutes' => 60,
    'closed_label' => '日曜・祝日',
    // 日曜以外の休診日（祝日）。年に1回まとめて更新する（カウンセリング予約の日付チェックに使う）
    'closed_dates' => [
        '2026-10-12', '2026-11-03', '2026-11-23',
        '2027-01-01', '2027-01-11', '2027-02-11', '2027-02-23', '2027-03-22', '2027-04-29',
        '2027-05-03', '2027-05-04', '2027-05-05', '2027-07-19', '2027-08-11',
        '2027-09-20', '2027-09-23', '2027-10-11', '2027-11-03', '2027-11-23',
    ],
    'reviewer' => ['name' => '桐生 遼', 'title' => '院長・形成外科専門医', 'role' => '院長', 'license' => '形成外科専門医'],
    'counseling_fee' => 3300,
    'counseling_minutes' => 60,
    'map_url' => 'https://www.google.com/maps/search/?api=1&query=%E6%9D%B1%E4%BA%AC%E9%83%BD%E4%B8%AD%E5%A4%AE%E5%8C%BA%E9%8A%80%E5%BA%A70-0-0',
    'theme_color' => '#111418',
    'og_image' => 'img/ogp.png',
    // ヘッダー・フッター・ドロワーのナビゲーション（href は url() に渡す値）
    'nav' => [
        ['id' => 'treatments', 'href' => 'index#treatments', 'label' => '施術メニュー', 'en' => 'Treatments'],
        ['id' => 'cases', 'href' => 'cases', 'label' => '症例写真', 'en' => 'Cases'],
        ['id' => 'doctor', 'href' => 'doctor', 'label' => '医師紹介', 'en' => 'Doctor'],
        ['id' => 'access', 'href' => 'index#access', 'label' => '診療時間・アクセス', 'en' => 'Access'],
    ],
];
