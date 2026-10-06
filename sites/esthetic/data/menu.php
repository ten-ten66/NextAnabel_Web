<?php
/**
 * メニューと料金（架空）
 * amount はすべて税込の整数。courses の months は有効期間（か月）。
 * 期間1か月超かつ5万円超のコースは、テンプレート側で data-course="qualifying" を付ける。
 * 純粋な配列のみを返すこと。
 */

return [
    'categories' => [
        [
            'id' => 'facial',
            'label' => 'フェイシャル',
            'en' => 'Facial',
            'lead' => 'クレンジングからパックまで、手のひらで包むように進めます。',
            'image' => 'leaves-mauve.webp',
            'items' => [
                [
                    'name' => '季節のフェイシャル',
                    'featured' => true,
                    'summary' => '季節の植物のオイルで、クレンジングからパックまで。肌が整う心地よさを、手のひらの温度で。',
                    'plans' => [
                        ['minutes' => 60, 'amount' => 13200],
                        ['minutes' => 90, 'amount' => 18700, 'note' => 'デコルテ・肩まで'],
                    ],
                ],
                [
                    'name' => '静かなフェイシャル',
                    'summary' => '香りが苦手な方や、その日の気分に合わせて。無香料のオイルとクリームで行います。',
                    'plans' => [
                        ['minutes' => 60, 'amount' => 13200],
                    ],
                ],
            ],
        ],
        [
            'id' => 'body',
            'label' => 'ボディトリートメント',
            'en' => 'Body',
            'lead' => '温めた石と麻のタオルで、からだの重さをゆっくりほどいていきます。',
            'image' => 'stones.webp',
            'items' => [
                [
                    'name' => '温石と麻のボディ',
                    'featured' => true,
                    'summary' => '温めた石と麻のタオルで、背中から脚までをゆっくりほぐします。むくみが気になる脚も、温めながら丁寧に。',
                    'plans' => [
                        ['minutes' => 90, 'amount' => 17600],
                        ['minutes' => 120, 'amount' => 23100, 'note' => 'ヘッドスパ付き'],
                    ],
                ],
                [
                    'name' => '背中と肩のトリートメント',
                    'summary' => 'デスクワークのあとに。肩甲骨のまわりを中心に、温めながらほぐします。',
                    'plans' => [
                        ['minutes' => 60, 'amount' => 11000],
                    ],
                ],
                [
                    'name' => '脚のトリートメント',
                    'summary' => 'むくみが気になる脚を、温めながらほぐします。立ち仕事の日の帰り道にも。',
                    'plans' => [
                        ['minutes' => 45, 'amount' => 8800],
                        ['minutes' => 60, 'amount' => 11000],
                    ],
                ],
            ],
        ],
        [
            'id' => 'headspa',
            'label' => 'ヘッドスパ',
            'en' => 'Head spa',
            'lead' => 'ハーブを浸したお湯で頭を温め、首すじから頭をほぐします。',
            'image' => 'leaves-room.webp',
            'items' => [
                [
                    'name' => '森のヘッドスパ',
                    'featured' => true,
                    'summary' => 'ハーブを浸したお湯で頭を温め、首すじから頭をほぐします。目を閉じて、呼吸が深くなる時間を。',
                    'plans' => [
                        ['minutes' => 45, 'amount' => 7700],
                        ['minutes' => 60, 'amount' => 9900, 'note' => '首・肩まで'],
                    ],
                ],
                [
                    'name' => 'ヘッドスパとフェイシャル',
                    'summary' => '森のヘッドスパと季節のフェイシャルを、ひと続きで。はじめての方にもおすすめです。',
                    'plans' => [
                        ['minutes' => 90, 'amount' => 18700],
                    ],
                ],
            ],
        ],
    ],
    'options' => [
        ['name' => '季節の足湯', 'minutes' => 10, 'amount' => 1100, 'summary' => '施術の前に。季節の植物を浮かべたお湯で足を温めます。'],
        ['name' => '目もとの温め', 'minutes' => 10, 'amount' => 1100, 'summary' => '小豆を詰めた布のピローで、目のまわりを温めます。'],
        ['name' => 'ハンドトリートメント', 'minutes' => 15, 'amount' => 2200, 'summary' => '指先から肘までを、オイルでゆっくりほぐします。'],
    ],
    'courses' => [
        ['name' => '季節のフェイシャル 3回券', 'detail' => '60分を3回', 'months' => 3, 'sessions' => 3, 'amount' => 36300],
        ['name' => '季節のフェイシャル 6回券', 'detail' => '60分を6回', 'months' => 6, 'sessions' => 6, 'amount' => 66000],
        ['name' => '温石と麻のボディ 4回券', 'detail' => '90分を4回', 'months' => 4, 'sessions' => 4, 'amount' => 63800],
        ['name' => '森のヘッドスパ 5回券', 'detail' => '45分を5回', 'months' => 3, 'sessions' => 5, 'amount' => 34100],
        ['name' => '季節めぐりのコース', 'detail' => '季節のフェイシャル60分と森のヘッドスパ45分を、各6回', 'months' => 12, 'sessions' => 12, 'amount' => 112200],
    ],
];
