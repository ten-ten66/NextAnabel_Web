<?php
/**
 * 予約フォーム（ショートコード [hakuji_contact]）
 *
 * 送信は admin-post.php で受け取り、検証後に wp_mail で送信してリダイレクトする（PRG）。
 * nonce による CSRF 対策、ハニーポット、IP ごとの送信間隔制限を行い、
 * 自動返信メールには利用者の自由記述を含めない（迷惑メールの中継に使われないようにする）。
 *
 * @package hakuji
 */

declare(strict_types=1);

const HAKUJI_FORM_ACTION = 'hakuji_contact';

function hakuji_form_fields(): array
{
    $treatments = get_posts(['post_type' => 'treatment', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
    return [
        'name' => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'autocomplete' => 'name'],
        'kana' => ['label' => 'フリガナ', 'type' => 'text', 'required' => true, 'autocomplete' => 'off'],
        'email' => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'],
        'tel' => ['label' => '電話番号', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel'],
        'treatment' => ['label' => '気になる施術', 'type' => 'select', 'required' => false,
            'options' => ['' => '選択してください'] + wp_list_pluck($treatments, 'post_title', 'post_name')],
        'date1' => ['label' => '第1希望日', 'type' => 'date', 'required' => true],
        'message' => ['label' => 'ご相談内容', 'type' => 'textarea', 'required' => false],
        'consent' => ['label' => 'プライバシーポリシーに同意する', 'type' => 'checkbox', 'required' => true],
    ];
}

/**
 * @param array<string, string> $input
 * @return array{0: array<string, string>, 1: array<string, string>}
 */
function hakuji_validate_contact(array $input): array
{
    $values = [];
    $errors = [];
    foreach (hakuji_form_fields() as $key => $field) {
        $raw = (string) ($input[$key] ?? '');
        $value = $field['type'] === 'textarea' ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        $values[$key] = $value;
        if ($field['required'] && $value === '') {
            $errors[$key] = $field['label'] . (in_array($field['type'], ['select', 'date', 'checkbox'], true) ? 'を選択してください。' : 'を入力してください。');
            continue;
        }
        if ($value === '') {
            continue;
        }
        $error = match ($key) {
            'email' => is_email($value) ? null : 'メールアドレスの形式が正しくありません。',
            'tel' => preg_match('/\A0\d{1,4}-?\d{1,4}-?\d{3,4}\z/', $value) ? null : '電話番号は半角数字とハイフンで入力してください。',
            'kana' => preg_match('/\A[ァ-ヶー・\x{3000} ]+\z/u', $value) ? null : 'フリガナはカタカナで入力してください。',
            'treatment' => array_key_exists($value, $field['options']) ? null : '気になる施術を選択してください。',
            'date1' => (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) && $value > wp_date('Y-m-d')) ? null : '第1希望日は明日以降の日付を選択してください。',
            'message' => mb_strlen($value) <= 1000 ? null : 'ご相談内容は1000文字以内で入力してください。',
            default => null,
        };
        if ($error !== null) {
            $errors[$key] = $error;
        }
    }
    return [$values, $errors];
}

add_shortcode('hakuji_contact', static function (): string {
    $fields = hakuji_form_fields();
    $state = ['values' => [], 'errors' => []];
    $token = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
    if ($token !== '' && ($saved = get_transient('hakuji_form_' . $token))) {
        $state = $saved;
        delete_transient('hakuji_form_' . $token);
    }
    ob_start();
    if (isset($_GET['sent'])) {
        echo '<div class="c-form__complete" role="status"><p>ご予約のお申し込みを受け付けました。確認のメールをお送りしましたので、内容をご確認ください。</p></div>';
        return (string) ob_get_clean();
    }
    if ($state['errors']) {
        echo '<div class="c-form__notice" role="alert"><p>入力内容に誤りがあります。各項目のメッセージをご確認ください。</p></div>';
    }
    echo '<form class="c-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" novalidate>';
    echo '<input type="hidden" name="action" value="' . esc_attr(HAKUJI_FORM_ACTION) . '">';
    wp_nonce_field(HAKUJI_FORM_ACTION, '_hakuji_nonce');
    echo '<div class="c-form__trap" aria-hidden="true"><label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
    foreach ($fields as $key => $field) {
        $id = 'hakuji-' . $key;
        $value = (string) ($state['values'][$key] ?? '');
        $error = $state['errors'][$key] ?? '';
        $describedBy = $error !== '' ? ' aria-invalid="true" aria-describedby="' . esc_attr($id . '-error') . '"' : '';
        $required = $field['required'] ? ' required' : '';
        echo '<div class="c-form__row">';
        if ($field['type'] === 'checkbox') {
            echo '<label class="c-form__check"><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" value="1"' . checked($value, '1', false) . $required . $describedBy . '> ' . esc_html($field['label']) . '</label>';
        } else {
            echo '<label class="c-form__label" for="' . esc_attr($id) . '">' . esc_html($field['label']) . ($field['required'] ? '<span class="c-form__required">必須</span>' : '') . '</label>';
            if ($field['type'] === 'textarea') {
                echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" rows="5" maxlength="1000"' . $describedBy . '>' . esc_textarea($value) . '</textarea>';
            } elseif ($field['type'] === 'select') {
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($key) . '"' . $required . $describedBy . '>';
                foreach ($field['options'] as $optValue => $optLabel) {
                    echo '<option value="' . esc_attr((string) $optValue) . '"' . selected($value, (string) $optValue, false) . '>' . esc_html($optLabel) . '</option>';
                }
                echo '</select>';
            } else {
                $auto = isset($field['autocomplete']) ? ' autocomplete="' . esc_attr($field['autocomplete']) . '"' : '';
                echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . $auto . $required . $describedBy . '>';
            }
        }
        if ($error !== '') {
            echo '<p class="c-form__error" id="' . esc_attr($id . '-error') . '">' . esc_html($error) . '</p>';
        }
        echo '</div>';
    }
    echo '<p class="c-form__submit"><button class="c-button" type="submit">この内容で送信する</button></p></form>';
    return (string) ob_get_clean();
});

function hakuji_handle_contact(): void
{
    $back = wp_get_referer() ?: home_url('/');
    $back = remove_query_arg(['form', 'sent'], $back);
    $nonce = isset($_POST['_hakuji_nonce']) ? sanitize_text_field(wp_unslash($_POST['_hakuji_nonce'])) : '';
    if (!wp_verify_nonce($nonce, HAKUJI_FORM_ACTION)) {
        wp_die('一定時間が経過したため、送信内容を確認できませんでした。お手数ですが、もう一度ご入力ください。', '送信できませんでした', ['response' => 400, 'back_link' => true]);
    }
    // ハニーポットに値があればボットとみなし、成功したように見せて何もしない
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('sent', '1', $back), 303);
        exit;
    }
    $limitKey = 'hakuji_rate_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (get_transient($limitKey)) {
        wp_die('短時間に続けて送信されました。しばらく時間をおいてから送信してください。', '送信できませんでした', ['response' => 429, 'back_link' => true]);
    }

    [$values, $errors] = hakuji_validate_contact(wp_unslash($_POST));
    if ($errors) {
        $token = wp_generate_password(20, false);
        set_transient('hakuji_form_' . strtolower($token), ['values' => $values, 'errors' => $errors], 10 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('form', strtolower($token), $back) . '#contact-form', 303);
        exit;
    }

    $clinic = hakuji_clinic();
    $fields = hakuji_form_fields();
    $lines = [];
    foreach ($fields as $key => $field) {
        $lines[] = '■' . $field['label'] . "\n" . ($values[$key] !== '' ? $values[$key] : '（未入力）');
    }
    $sentAdmin = wp_mail($clinic['mail_to'], '【Web予約】' . $values['name'] . ' 様', implode("\n\n", $lines), ['Reply-To: ' . $values['email']]);
    $reply = $values['name'] . " 様\n\nこのたびはご予約のお申し込みをいただき、ありがとうございます。\n担当者より、ご希望日時の空き状況をご連絡いたします。\n\n第1希望日：" . $values['date1']
        . "\n\n" . $clinic['name'] . "\nTEL " . $clinic['tel'] . "\n※このメールは送信専用です。";
    wp_mail($values['email'], '【' . $clinic['name'] . '】ご予約のお申し込みを受け付けました', $reply);

    if (!$sentAdmin) {
        wp_die('送信できませんでした。時間をおいて再度お試しいただくか、お電話でお問い合わせください。', '送信できませんでした', ['response' => 500, 'back_link' => true]);
    }
    set_transient($limitKey, 1, 30);
    wp_safe_redirect(add_query_arg('sent', '1', $back) . '#contact-form', 303);
    exit;
}
add_action('admin_post_' . HAKUJI_FORM_ACTION, 'hakuji_handle_contact');
add_action('admin_post_nopriv_' . HAKUJI_FORM_ACTION, 'hakuji_handle_contact');
