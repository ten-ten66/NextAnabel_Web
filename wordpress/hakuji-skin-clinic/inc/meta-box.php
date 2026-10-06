<?php
/**
 * 施術の必須表示を入力するメタボックス
 *
 * 費用・回数の目安・ダウンタイム・リスク・最終確認日（未承認医薬品等を使う場合はその5項目）が
 * 揃っていない施術は、公開しようとしても下書きに戻し、不足している項目を通知する。
 *
 * @package hakuji
 */

declare(strict_types=1);

add_action('add_meta_boxes_treatment', static function (): void {
    add_meta_box('hakuji-disclosure', '施術の必須表示（費用・リスクなど）', 'hakuji_render_meta_box', 'treatment', 'normal', 'high');
});

function hakuji_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('hakuji_save_meta', '_hakuji_meta_nonce');
    echo '<p>ここに入力した内容は、施術ページで価格の隣に本文と同じ大きさで表示されます。</p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    foreach (hakuji_meta_fields() as $key => [$label, $type, $help]) {
        $value = (string) get_post_meta($post->ID, $key, true);
        $id = esc_attr(ltrim($key, '_'));
        echo '<tr><th scope="row"><label for="' . $id . '">' . esc_html($label) . '</label></th><td>';
        if ($type === 'textarea') {
            echo '<textarea class="large-text" rows="4" id="' . $id . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
        } elseif ($type === 'checkbox') {
            echo '<input type="checkbox" id="' . $id . '" name="' . esc_attr($key) . '" value="1"' . checked($value, '1', false) . '>';
        } else {
            echo '<input class="regular-text" type="' . esc_attr($type) . '" id="' . $id . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        if ($help !== '') {
            echo '<p class="description">' . esc_html($help) . '</p>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

function hakuji_save_meta(int $postId): void
{
    if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
        return;
    }
    $nonce = isset($_POST['_hakuji_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['_hakuji_meta_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'hakuji_save_meta') || !current_user_can('edit_post', $postId)) {
        return;
    }
    foreach (hakuji_meta_fields() as $key => [, $type]) {
        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        $value = match ($type) {
            'textarea' => sanitize_textarea_field((string) $raw),
            'checkbox' => $raw === '1' ? '1' : '',
            'date' => preg_match('/\A\d{4}-\d{2}-\d{2}\z/', (string) $raw) ? (string) $raw : '',
            default => sanitize_text_field((string) $raw),
        };
        update_post_meta($postId, $key, $value);
    }
}
add_action('save_post_treatment', 'hakuji_save_meta');

/**
 * 必須表示が揃っていない施術は公開・予約投稿させない。
 * メタ情報とタームの保存がすべて終わった後に呼ばれる wp_after_insert_post で確認するため、
 * 編集画面だけでなく、クイック編集・REST API（show_in_rest）からの公開にも同じ条件がかかる。
 */
add_action('wp_after_insert_post', static function (int $postId, WP_Post $post): void {
    if ($post->post_type !== 'treatment' || !in_array($post->post_status, ['publish', 'future'], true) || wp_is_post_revision($postId)) {
        return;
    }
    $missing = hakuji_missing_disclosures($postId);
    if (!$missing) {
        return;
    }
    // 下書きに戻すと wp_after_insert_post がもう一度呼ばれるが、状態が draft になるため繰り返さない
    wp_update_post(['ID' => $postId, 'post_status' => 'draft']);
    set_transient('hakuji_missing_' . get_current_user_id(), $missing, MINUTE_IN_SECONDS);
}, 10, 2);

// 下書きに戻したときは、公開済みのメッセージではなく不足項目を表示する
add_filter('redirect_post_location', static function (string $location): string {
    if (get_transient('hakuji_missing_' . get_current_user_id())) {
        return remove_query_arg('message', $location);
    }
    return $location;
});

add_action('admin_notices', static function (): void {
    $key = 'hakuji_missing_' . get_current_user_id();
    $missing = get_transient($key);
    if (!$missing) {
        return;
    }
    delete_transient($key);
    echo '<div class="notice notice-error"><p><strong>必須表示が不足しているため、下書きとして保存しました。</strong></p><ul style="list-style:disc;padding-left:1.5em">';
    foreach ((array) $missing as $label) {
        echo '<li>' . esc_html($label) . '</li>';
    }
    echo '</ul></div>';
});

// 施術一覧に「必須表示」の列を追加する
add_filter('manage_treatment_posts_columns', static function (array $columns): array {
    $columns['hakuji_disclosure'] = '必須表示';
    return $columns;
});
add_action('manage_treatment_posts_custom_column', static function (string $column, int $postId): void {
    if ($column !== 'hakuji_disclosure') {
        return;
    }
    $missing = hakuji_missing_disclosures($postId);
    echo $missing
        ? '<span style="color:#b32d2e">不足: ' . esc_html(implode('、', $missing)) . '</span>'
        : '<span style="color:#007017">揃っています</span>';
}, 10, 2);
