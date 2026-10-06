<?php
/**
 * 医院情報（架空）。静的サイト版の sites/skin-clinic/data/site.php と同じ値。
 * テーマ単体で配布できるよう、テーマ内に持たせている。
 *
 * @package hakuji
 */

return [
    'name' => '白磁スキンクリニック',
    'name_en' => 'HAKUJI SKIN CLINIC',
    'tel' => '03-0000-0000',
    'tel_intl' => '+81-3-0000-0000',
    'email' => 'info@example.com',
    'mail_to' => 'reservation@example.com',
    'postal_code' => '000-0000',
    'region' => '東京都',
    'locality' => '港区',
    'street' => '南青山0-0-0 サンプルビル3階',
    'access' => [
        '東京メトロ「表参道」駅 A4出口から徒歩4分',
        '東京メトロ「外苑前」駅 1a出口から徒歩8分',
    ],
    'hours' => [
        ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Friday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00', 'label' => '月・火・水・金・土'],
        ['days' => ['Sunday'], 'opens' => '10:00', 'closes' => '17:00', 'label' => '日'],
    ],
    'closed' => '木曜・祝日',
    'reviewer' => ['name' => '汐見 透子', 'title' => '院長・皮膚科専門医'],
];
