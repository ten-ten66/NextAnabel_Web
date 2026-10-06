<?php
/**
 * 施術（カスタム投稿タイプ）・悩みカテゴリー（タクソノミー）・メタ情報の登録
 *
 * @package hakuji
 */

declare(strict_types=1);

/** 未承認医薬品・医療機器を使う場合の必須表示5項目 */
const HAKUJI_UNAPPROVED_ITEMS = [
    'status' => '未承認医薬品等であること',
    'route' => '入手経路',
    'domestic' => '国内の承認医薬品等の有無',
    'overseas' => '諸外国における安全性等に関する情報',
    'relief' => '医薬品副作用被害救済制度の対象外であること',
];

/** 施術のメタ情報（キー => [ラベル, 入力形式, 説明]） */
function hakuji_meta_fields(): array
{
    $fields = [
        '_hakuji_en' => ['英語表記', 'text', '例: Intense Pulsed Light（見出しの装飾に使います）'],
        '_hakuji_lead' => ['一覧用の短い説明', 'text', '例: 顔全体のシミ・そばかす、くすみ、赤みに。'],
        '_hakuji_image' => ['画像', 'text', 'テーマの assets/img/ 内のファイル名。例: pearl-spots.webp'],
        '_hakuji_for' => ['こんな方に', 'textarea', '1行に1項目。'],
        '_hakuji_flow' => ['施術の流れ', 'textarea', '1行に1手順。「見出し：説明」の形で書くと見出しが付きます。'],
        '_hakuji_prices' => ['費用（税込）', 'textarea', '1行に「ラベル|金額」。例: 全顔 1回|22000'],
        '_hakuji_courses' => ['コース', 'textarea', '1行に「ラベル|期間（月）|金額」。期間1か月超かつ5万円超のコースにはクーリング・オフの案内が自動で表示されます。'],
        '_hakuji_price_note' => ['料金の補足', 'textarea', ''],
        '_hakuji_sessions' => ['回数・期間の目安', 'textarea', '個人差がある旨も記載してください。'],
        '_hakuji_sessions_short' => ['回数の目安（短い表記）', 'text', '例: 3〜4週間ごと・5回程度'],
        '_hakuji_duration' => ['施術時間', 'text', '例: 約20分'],
        '_hakuji_anesthesia' => ['麻酔', 'text', '例: 通常は使用しません'],
        '_hakuji_downtime' => ['ダウンタイム', 'textarea', ''],
        '_hakuji_downtime_short' => ['ダウンタイム（短い表記）', 'text', '例: 赤み 数時間〜1日程度'],
        '_hakuji_risks' => ['主なリスク・副作用', 'textarea', '1行に1項目。'],
        '_hakuji_risks_short' => ['主なリスク（一覧用の短い表記）', 'text', ''],
        '_hakuji_contraindications' => ['施術を受けられない方', 'textarea', '1行に1項目。'],
        '_hakuji_aftercare' => ['アフターケア', 'textarea', '1行に1項目。'],
        '_hakuji_reviewed' => ['最終確認日', 'date', '監修医が内容を確認した日'],
        '_hakuji_unapproved' => ['未承認医薬品・医療機器を使用する', 'checkbox', 'チェックすると下の5項目が必須になります。'],
    ];
    foreach (HAKUJI_UNAPPROVED_ITEMS as $key => $label) {
        $fields['_hakuji_unapproved_' . $key] = [$label, 'textarea', ''];
    }
    return $fields;
}

add_action('init', static function (): void {
    register_post_type('treatment', [
        'labels' => [
            'name' => '施術',
            'singular_name' => '施術',
            'add_new_item' => '施術を追加',
            'edit_item' => '施術を編集',
            'all_items' => '施術一覧',
            'search_items' => '施術を検索',
            'not_found' => '施術が見つかりません',
        ],
        'public' => true,
        'has_archive' => 'treatments',
        'rewrite' => ['slug' => 'treatments', 'with_front' => false],
        'menu_icon' => 'dashicons-clipboard',
        'menu_position' => 5,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('concern', 'treatment', [
        'labels' => ['name' => 'お悩み', 'singular_name' => 'お悩み', 'add_new_item' => 'お悩みを追加'],
        'hierarchical' => true,
        'public' => true,
        'rewrite' => ['slug' => 'concern', 'with_front' => false],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);

    foreach (hakuji_meta_fields() as $key => [, $type]) {
        register_post_meta('treatment', $key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
            'auth_callback' => static fn () => current_user_can('edit_posts'),
        ]);
    }
});

// 施術はメタボックスで必須表示を入力するため、クラシックエディターで編集する
add_filter('use_block_editor_for_post_type', static fn (bool $use, string $type): bool => $type === 'treatment' ? false : $use, 10, 2);

// 施術一覧は表示順（menu_order）で並べる
add_action('pre_get_posts', static function (WP_Query $query): void {
    if (!is_admin() && $query->is_main_query() && ($query->is_post_type_archive('treatment') || $query->is_tax('concern'))) {
        $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'ASC']);
        $query->set('posts_per_page', 50);
    }
});
