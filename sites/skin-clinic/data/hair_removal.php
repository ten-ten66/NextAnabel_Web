<?php
/**
 * 医療レーザー脱毛の料金・回数データ（架空・税込）
 * コーポレートサイト（skin-clinic の施術詳細・料金表）と医療脱毛LP（lp のシミュレーター）が共通で読み込む。
 * 料金を変えるときはこのファイルだけを直せば、両方のサイトに反映される。
 *
 * once    : 1回の料金（税込）
 * course  : 5回コースの総額（税込）
 * side    : 人体図のどちら側に表示するか（front / back）
 */

return [
    'course' => [
        'count' => 5,
        'months' => 10,
        'interval' => '顔は1〜1.5か月、体は2〜3か月に1回が目安です。',
        'note' => '毛の量や毛質、肌の状態によって必要な回数には個人差があります。5回で終了とならない場合があります。',
    ],
    'parts' => [
        ['id' => 'face', 'label' => '顔（額・頬・鼻下・あご）', 'side' => 'front', 'once' => 16500, 'course' => 74800],
        ['id' => 'neck', 'label' => '首（前・うなじ）', 'side' => 'front', 'once' => 8800, 'course' => 39600],
        ['id' => 'underarm', 'label' => '両ワキ', 'side' => 'front', 'once' => 4400, 'course' => 19800],
        ['id' => 'arms', 'label' => '両腕（ひじ上・ひじ下）', 'side' => 'front', 'once' => 22000, 'course' => 99000],
        ['id' => 'hands', 'label' => '両手の甲・指', 'side' => 'front', 'once' => 5500, 'course' => 24200],
        ['id' => 'chest', 'label' => '胸・お腹', 'side' => 'front', 'once' => 19800, 'course' => 89100],
        ['id' => 'vio', 'label' => 'VIO', 'side' => 'front', 'once' => 22000, 'course' => 99000],
        ['id' => 'legs', 'label' => '両脚（ひざ上・ひざ下）', 'side' => 'front', 'once' => 33000, 'course' => 148500],
        ['id' => 'feet', 'label' => '両足の甲・指', 'side' => 'front', 'once' => 5500, 'course' => 24200],
        ['id' => 'back', 'label' => '背中（上・下）', 'side' => 'back', 'once' => 22000, 'course' => 99000],
        ['id' => 'hip', 'label' => 'ヒップ', 'side' => 'back', 'once' => 11000, 'course' => 49500],
    ],
    // セットプラン。includes の部位をすべて選んだときは、合計ではなくセット料金で計算する
    'sets' => [
        [
            'id' => 'full-body',
            'label' => '全身（顔・VIOを除く）',
            'includes' => ['neck', 'underarm', 'arms', 'hands', 'chest', 'legs', 'feet', 'back', 'hip'],
            'once' => 66000,
            'course' => 297000,
        ],
        [
            'id' => 'full-body-all',
            'label' => '全身（顔・VIOを含む）',
            'includes' => ['face', 'neck', 'underarm', 'arms', 'hands', 'chest', 'vio', 'legs', 'feet', 'back', 'hip'],
            'once' => 93500,
            'course' => 418000,
        ],
    ],
    // 料金に含まれるもの・別途かかるもの（総額の誤認を防ぐため明記する）
    'included' => ['照射料', '照射前後の冷却', 'シェービング（背中・うなじなど手の届きにくい部位）'],
    'extra' => [
        ['label' => '麻酔クリーム（1部位）', 'amount' => 3300],
        ['label' => '肌トラブル時の診察・お薬', 'amount' => 0, 'note' => '照射が原因と医師が判断した場合は無料'],
    ],
];
