<?php
/**
 * 予約フォーム（ショートコード [hakuji_contact]）
 *
 * マークアップは静的サイト版（sites/skin-clinic/contact.php）と同じクラスを使い、同じ CSS で表示する。
 * 送信は admin-post.php で受け取り、検証後に wp_mail で送信してリダイレクトする（PRG）。
 * nonce による CSRF 対策、ハニーポット、IP ごとの送信間隔制限を行い、
 * 自動返信メールには利用者の自由記述を含めない（迷惑メールの中継に使われないようにする）。
 *
 * @package hakuji
 */

declare(strict_types=1);

const HAKUJI_FORM_ACTION = 'hakuji_contact';
const HAKUJI_FORM_MAX_DAYS = 60;

/**
 * 入力項目
 *
 * @return array<string, array<string, mixed>>
 */
function hakuji_form_fields(): array
{
    $treatments = get_posts(['post_type' => 'treatment', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
    return [
        'name' => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'autocomplete' => 'name', 'maxlength' => 40],
        'kana' => ['label' => 'フリガナ', 'type' => 'text', 'required' => true, 'maxlength' => 60,
            'hint' => '全角カタカナでご入力ください。ひらがなで入力された場合は、カタカナに直して受け付けます。'],
        'email' => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'maxlength' => 254,
            'hint' => '受付内容の控えをお送りします。'],
        'tel' => ['label' => '電話番号', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'maxlength' => 20, 'short' => true,
            'hint' => '日中につながりやすい番号をご入力ください（ハイフンはあってもなくてもかまいません）。'],
        'menu' => ['label' => '気になる施術', 'type' => 'select', 'required' => false,
            'options' => ['' => '選択してください（未定の場合はそのまま）'] + wp_list_pluck($treatments, 'post_title', 'post_name')],
        'date1' => ['label' => '第1希望日', 'type' => 'date', 'required' => true,
            'hint' => '明日から' . HAKUJI_FORM_MAX_DAYS . '日先までの、休診日（' . hakuji_clinic('closed') . '）以外の日付を選べます。'],
        'time' => ['label' => '時間帯', 'type' => 'choice', 'required' => true,
            'options' => ['am' => '10:00〜12:00', 'midday' => '12:00〜15:00', 'pm' => '15:00〜18:30', 'any' => '指定なし'],
            'hint' => '日曜日は16:30が最終受付です。'],
        'message' => ['label' => 'ご相談内容', 'type' => 'textarea', 'required' => false, 'maxlength' => 1000,
            'hint' => '気になっている症状や、これまでに受けた治療などをご記入ください（1000文字以内）。'],
        'consent' => ['label' => 'プライバシーポリシーに同意する', 'type' => 'checkbox', 'required' => true],
    ];
}

/** 2026-10-09 → 2026年10月9日（金） */
function hakuji_ja_date(string $ymd): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $date ? $date->format('Y年n月j日') . '（' . HAKUJI_WEEKDAYS[(int) $date->format('w')] . '）' : $ymd;
}

/** 希望日として選べるか（明日以降・上限日数以内・休診日以外） */
function hakuji_date_error(string $value, string $label): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
    if (!$date || $date->format('Y-m-d') !== $value) {
        return $label . 'の形式が正しくありません。';
    }
    $today = new DateTimeImmutable('today', wp_timezone());
    if ($date <= $today || $date > $today->modify('+' . HAKUJI_FORM_MAX_DAYS . ' days')) {
        return $label . 'は、明日から' . HAKUJI_FORM_MAX_DAYS . '日先までの日付を選択してください。';
    }
    $schedule = (array) hakuji_clinic('schedule');
    if (($schedule[(int) $date->format('w')] ?? null) === null || in_array($value, (array) hakuji_clinic('closed_dates'), true)) {
        return $label . 'は休診日（' . hakuji_clinic('closed') . '）以外の日付を選択してください。';
    }
    return null;
}

/**
 * @param array<string, mixed> $input
 * @return array{0: array<string, string>, 1: array<string, string>}
 */
function hakuji_validate_contact(array $input): array
{
    $values = [];
    $errors = [];
    foreach (hakuji_form_fields() as $key => $field) {
        $raw = is_string($input[$key] ?? null) ? $input[$key] : '';
        $value = $field['type'] === 'textarea' ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
        if ($key === 'kana') {
            // ひらがな・半角カナは全角カタカナに直して受け付ける
            $value = mb_convert_kana($value, 'KVC');
        }
        if ($key === 'tel') {
            $value = mb_convert_kana($value, 'a');
        }
        $values[$key] = $value;
        if ($field['required'] && $value === '') {
            $errors[$key] = $field['label'] . (in_array($field['type'], ['select', 'date', 'choice'], true) ? 'を選択してください。'
                : ($field['type'] === 'checkbox' ? 'にチェックを入れてください。' : 'を入力してください。'));
            continue;
        }
        if ($value === '') {
            continue;
        }
        if (isset($field['maxlength']) && mb_strlen($value) > $field['maxlength']) {
            $errors[$key] = $field['label'] . 'は' . $field['maxlength'] . '文字以内で入力してください。';
            continue;
        }
        $error = match ($key) {
            'email' => is_email($value) ? null : 'メールアドレスの形式が正しくありません。',
            'tel' => preg_match('/\A0\d{1,4}-?\d{1,4}-?\d{3,4}\z/', $value) ? null : '電話番号は、0から始まる半角数字（ハイフン可）で入力してください。',
            'kana' => preg_match('/\A[ァ-ヶー・\x{3000} ]+\z/u', $value) ? null : 'フリガナはカタカナで入力してください。',
            'menu', 'time' => array_key_exists($value, $field['options']) ? null : $field['label'] . 'を選択肢から選んでください。',
            'date1' => hakuji_date_error($value, $field['label']),
            'consent' => $value === '1' ? null : $field['label'] . 'にチェックを入れてください。',
            default => null,
        };
        if ($error !== null) {
            $errors[$key] = $error;
        }
    }
    return [$values, $errors];
}

/** 必須・任意のバッジ */
function hakuji_form_badge(bool $required): string
{
    return $required
        ? '<span class="c-field__badge c-field__badge--required">必須</span>'
        : '<span class="c-field__badge">任意</span>';
}

/** 1項目分のマークアップ（静的サイト版と同じ c-field の構造） */
function hakuji_form_field(string $key, array $field, string $value, string $error): string
{
    $id = 'f-' . $key;
    $hintId = isset($field['hint']) ? $id . '-hint' : '';
    $describedBy = trim($hintId . ($error !== '' ? ' ' . $id . '-error' : ''));
    $aria = ($error !== '' ? ' aria-invalid="true"' : '') . ($describedBy !== '' ? ' aria-describedby="' . esc_attr($describedBy) . '"' : '');
    $required = $field['required'] ? ' required' : '';
    $hint = $hintId !== '' ? '<p class="c-field__hint" id="' . esc_attr($hintId) . '">' . esc_html($field['hint']) . '</p>' : '';
    $errorHtml = $error !== '' ? '<p class="c-field__error" id="' . esc_attr($id . '-error') . '">' . esc_html($error) . '</p>' : '';
    $class = 'c-field' . ($error !== '' ? ' is-invalid' : '');

    if ($field['type'] === 'choice') {
        $html = '<fieldset class="' . esc_attr($class . ' c-field--group') . '" id="' . esc_attr($id) . '" role="radiogroup"' . ($field['required'] ? ' aria-required="true"' : '') . $aria . '>';
        $html .= '<legend class="c-field__label">' . esc_html($field['label']) . hakuji_form_badge($field['required']) . '</legend>';
        $html .= '<div class="c-choice-list c-choice-list--compact">';
        foreach ($field['options'] as $optValue => $optLabel) {
            $html .= '<label class="c-choice"><input type="radio" name="' . esc_attr($key) . '" value="' . esc_attr((string) $optValue) . '"' . ($value === (string) $optValue ? ' checked' : '') . $required . '>'
                . '<span class="c-choice__label">' . esc_html($optLabel) . '</span></label>';
        }
        return $html . '</div>' . $hint . $errorHtml . '</fieldset>';
    }

    if ($field['type'] === 'checkbox') {
        $privacy = get_privacy_policy_url();
        $html = '<div class="' . esc_attr($class . ' c-field--consent') . '">';
        $html .= '<p class="c-consent__text" id="' . esc_attr($id . '-hint') . '">ご入力いただいた内容は、ご予約の受付とご連絡のために利用します。ご相談内容など健康に関する情報は、ご予約とカウンセリングのためにのみ利用します。'
            . ($privacy !== '' ? '詳しくは<a href="' . esc_url($privacy) . '">プライバシーポリシー</a>をご確認ください。' : '') . '</p>';
        $consentAria = ($error !== '' ? ' aria-invalid="true"' : '') . ' aria-describedby="' . esc_attr($id . '-hint' . ($error !== '' ? ' ' . $id . '-error' : '')) . '"';
        $html .= '<label class="c-checkbox"><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" value="1"' . ($value === '1' ? ' checked' : '') . $required . $consentAria . '>'
            . '<span class="c-checkbox__label">' . esc_html($field['label']) . '</span>' . hakuji_form_badge($field['required']) . '</label>';
        return $html . $errorHtml . '</div>';
    }

    $html = '<div class="' . esc_attr($class) . '">';
    $html .= '<label class="c-field__label" for="' . esc_attr($id) . '">' . esc_html($field['label']) . hakuji_form_badge($field['required']) . '</label>';
    $maxlength = isset($field['maxlength']) ? ' maxlength="' . (int) $field['maxlength'] . '"' : '';
    if ($field['type'] === 'textarea') {
        $html .= '<textarea class="c-field__input c-field__input--textarea" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" rows="6"' . $maxlength . $aria . '>' . esc_textarea($value) . '</textarea>';
    } elseif ($field['type'] === 'select') {
        $html .= '<div class="c-select"><select class="c-field__input" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '"' . $required . $aria . '>';
        foreach ($field['options'] as $optValue => $optLabel) {
            $html .= '<option value="' . esc_attr((string) $optValue) . '"' . ($value === (string) $optValue ? ' selected' : '') . '>' . esc_html($optLabel) . '</option>';
        }
        $html .= '</select></div>';
    } else {
        $modifier = $field['type'] === 'date' ? ' c-field__input--date' : (!empty($field['short']) ? ' c-field__input--short' : '');
        $extra = isset($field['autocomplete']) ? ' autocomplete="' . esc_attr($field['autocomplete']) . '"' : '';
        if ($field['type'] === 'date') {
            $today = new DateTimeImmutable('today', wp_timezone());
            $extra .= ' min="' . esc_attr($today->modify('+1 day')->format('Y-m-d')) . '" max="' . esc_attr($today->modify('+' . HAKUJI_FORM_MAX_DAYS . ' days')->format('Y-m-d')) . '"';
        }
        if ($field['type'] === 'email') {
            $extra .= ' spellcheck="false"';
        }
        $html .= '<input class="c-field__input' . $modifier . '" type="' . esc_attr($field['type']) . '" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . $extra . $maxlength . $required . $aria . '>';
    }
    return $html . $hint . $errorHtml . '</div>';
}

add_shortcode('hakuji_contact', static function (): string {
    $fields = hakuji_form_fields();
    $state = ['values' => [], 'errors' => []];
    $token = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
    if ($token !== '' && is_array($saved = get_transient('hakuji_form_' . $token))) {
        $state = $saved;
        delete_transient('hakuji_form_' . $token);
    }
    // 施術ページからのリンク（?menu=ipl）では、気になる施術を選んだ状態にする
    $preselect = isset($_GET['menu']) ? sanitize_title(wp_unslash($_GET['menu'])) : '';
    if (!isset($state['values']['menu']) && isset($fields['menu']['options'][$preselect])) {
        $state['values']['menu'] = $preselect;
    }

    if (isset($_GET['sent'])) {
        return '<div class="c-form-notice c-form-notice--done" role="status"><p class="c-form-notice__title">ご予約のお申し込みを受け付けました。</p>'
            . '<p>確認のメールをお送りしました。2営業日以内に当院からご連絡し、日時を確定いたします。</p></div>';
    }

    $html = '';
    if ($state['errors']) {
        $html .= '<div class="c-form-notice" role="alert"><p class="c-form-notice__title">入力内容をご確認ください（' . count($state['errors']) . '件）</p><ul class="c-form-notice__list">';
        foreach ($state['errors'] as $key => $message) {
            $html .= '<li><a href="#f-' . esc_attr($key) . '">' . esc_html($message) . '</a></li>';
        }
        $html .= '</ul></div>';
    }
    $html .= '<form class="c-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" novalidate>';
    $html .= '<input type="hidden" name="action" value="' . esc_attr(HAKUJI_FORM_ACTION) . '">';
    $html .= wp_nonce_field(HAKUJI_FORM_ACTION, 'hakuji_nonce', true, false);
    $html .= '<div class="c-form__hp" aria-hidden="true"><label for="f-website">ウェブサイト（入力しないでください）</label><input type="text" id="f-website" name="website" value="" tabindex="-1" autocomplete="off"></div>';

    $sections = [
        'お客さまの情報' => ['name', 'kana', 'email', 'tel'],
        'ご予約の内容' => ['menu', 'date1', 'time'],
        '' => ['message', 'consent'],
    ];
    foreach ($sections as $legend => $keys) {
        $html .= $legend !== '' ? '<fieldset class="c-form__section"><legend class="c-form__legend">' . esc_html($legend) . '</legend>' : '<div class="c-form__section">';
        foreach ($keys as $key) {
            $html .= hakuji_form_field($key, $fields[$key], (string) ($state['values'][$key] ?? ''), (string) ($state['errors'][$key] ?? ''));
        }
        $html .= $legend !== '' ? '</fieldset>' : '</div>';
    }
    $html .= '<div class="c-form__actions"><button type="submit" class="c-button c-button--primary c-button--large">この内容で送信する' . hakuji_icon('arrow') . '</button></div></form>';
    return $html;
});

function hakuji_handle_contact(): void
{
    $back = wp_get_referer() ?: hakuji_contact_url();
    $back = remove_query_arg(['form', 'sent'], $back);
    $nonce = isset($_POST['hakuji_nonce']) ? sanitize_text_field(wp_unslash($_POST['hakuji_nonce'])) : '';
    if (!wp_verify_nonce($nonce, HAKUJI_FORM_ACTION)) {
        wp_die('一定時間が経過したため、送信内容を確認できませんでした。お手数ですが、もう一度ご入力ください。', '送信できませんでした', ['response' => 400, 'back_link' => true]);
    }
    // ハニーポットに値があればボットとみなし、成功したように見せて何もしない
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('sent', '1', $back) . '#contact-form', 303);
        exit;
    }
    $limitKey = 'hakuji_rate_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (get_transient($limitKey)) {
        wp_die('短時間に続けて送信されました。しばらく時間をおいてから送信してください。', '送信できませんでした', ['response' => 429, 'back_link' => true]);
    }

    [$values, $errors] = hakuji_validate_contact(wp_unslash($_POST));
    if ($errors) {
        $token = strtolower(wp_generate_password(20, false));
        set_transient('hakuji_form_' . $token, ['values' => $values, 'errors' => $errors], 10 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('form', $token, $back) . '#contact-form', 303);
        exit;
    }

    $clinic = hakuji_clinic();
    $fields = hakuji_form_fields();
    $display = static fn (string $key): string => match (true) {
        $values[$key] === '' => '',
        in_array($fields[$key]['type'], ['select', 'choice'], true) => (string) ($fields[$key]['options'][$values[$key]] ?? ''),
        $fields[$key]['type'] === 'checkbox' => '同意する',
        $fields[$key]['type'] === 'date' => hakuji_ja_date($values[$key]),
        default => $values[$key],
    };
    $lines = [];
    foreach (array_keys($fields) as $key) {
        $lines[] = '■' . $fields[$key]['label'] . "\n" . ($display($key) !== '' ? $display($key) : '（未入力）');
    }
    $sentAdmin = wp_mail($clinic['mail_to'], '【Web予約】' . $values['name'] . ' 様', implode("\n\n", $lines), ['Reply-To: ' . $values['email']]);
    // 自動返信には利用者の自由記述（お名前・ご相談内容）を載せず、選択式の項目だけを控えとして送る
    $reply = "このたびはご予約のお申し込みをいただき、ありがとうございます。\n2営業日以内に、ご希望日時の空き状況をご連絡いたします。\n\n"
        . '第1希望日：' . $display('date1') . '　' . $display('time') . "\n\n"
        . $clinic['name'] . "\nTEL " . $clinic['tel'] . "\n※このメールは送信専用です。";
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
