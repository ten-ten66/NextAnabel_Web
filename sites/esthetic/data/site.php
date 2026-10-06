<?php
/**
 * 苔と麻（エステティックサロン）の基本情報（架空）
 * キーの構成は sites/skin-clinic/data/site.php に合わせている。
 * 純粋な配列のみを返すこと（表現チェックが直接読み込むため）。
 */

return [
    'name' => '苔と麻',
    'name_en' => 'koke to asa',
    'title_top' => '苔と麻（koke to asa）｜自由が丘のフェイシャル・ボディトリートメントサロン',
    'description' => '自由が丘のエステティックサロン「苔と麻」。季節の植物の香りと手のひらのぬくもりで、フェイシャル・ボディトリートメント・ヘッドスパの静かな時間をお届けします。料金はすべて税込で表示しています。',
    'base' => 'esthetic',
    'schema_type' => 'BeautySalon',
    'price_range' => '¥7,700〜',
    'tel' => '03-0000-0002',
    'tel_intl' => '+81-3-0000-0002',
    'email' => 'info@example.com',
    'address' => [
        'postal_code' => '000-0000',
        'region' => '東京都',
        'locality' => '目黒区',
        'street' => '自由が丘0-0-0 サンプルハウス2階',
    ],
    'access' => [
        '東急東横線・大井町線「自由が丘」駅 正面口から徒歩6分',
    ],
    // 構造化データ用（曜日は schema.org の DayOfWeek）
    'hours' => [
        ['days' => ['Monday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 'opens' => '11:00', 'closes' => '21:00'],
    ],
    // 画面表示・営業状況の計算用（0=日曜 … 6=土曜、null は定休日）
    'schedule' => [
        0 => ['open' => '11:00', 'close' => '21:00'],
        1 => ['open' => '11:00', 'close' => '21:00'],
        2 => null,
        3 => ['open' => '11:00', 'close' => '21:00'],
        4 => ['open' => '11:00', 'close' => '21:00'],
        5 => ['open' => '11:00', 'close' => '21:00'],
        6 => ['open' => '11:00', 'close' => '21:00'],
    ],
    'hours_label' => '11:00–21:00',
    'last_entry' => '19:30',
    'last_entry_minutes' => 90,
    'closed_label' => '火曜',
    'beds' => '個室2室',
    'payment' => '現金・クレジットカード・交通系ICカード・QRコード決済',
    'map_url' => 'https://www.google.com/maps/search/?api=1&query=%E8%87%AA%E7%94%B1%E3%81%8C%E4%B8%98%E9%A7%85',
    'theme_color' => '#ECEEE6',
    'og_image' => 'img/ogp.png',
    'demo_notice' => 'このサイトはWeb制作のサンプルとして作成した架空のサロンです。実在の店舗・人物とは関係ありません。',
    // 欧文の装飾書体（Fraunces）は、ここに含まれる文字だけを Google Fonts の text= で読み込む
    'accent_glyphs' => 'koke to asa About Season Menu Price Flow Space Therapists First visit FAQ Access Reserve Notes Course Options Facial Body Head spa Hands Quiet Not found 0123456789 / – — · & : . ( ) ,',
    'nav' => [
        ['id' => 'about', 'label' => 'はじめに', 'en' => 'About', 'href' => 'index#about'],
        ['id' => 'menu', 'label' => 'メニューと料金', 'en' => 'Menu & Price', 'href' => 'menu'],
        ['id' => 'flow', 'label' => '施術の流れ', 'en' => 'Flow', 'href' => 'index#flow'],
        ['id' => 'space', 'label' => '空間', 'en' => 'Space', 'href' => 'index#space'],
        ['id' => 'faq', 'label' => 'よくある質問', 'en' => 'FAQ', 'href' => 'index#faq'],
        ['id' => 'access', 'label' => 'アクセス', 'en' => 'Access', 'href' => 'index#access'],
    ],
];
