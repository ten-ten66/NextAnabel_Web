<?php
/**
 * カウンセリング予約フォームの定義・検証・メール文面
 * 画面（index.php）と API（api/reserve.php）が同じ定義を使い、選択肢や文言が食い違わないようにする。
 */

declare(strict_types=1);

use Core\Form\Mail;
use Core\Form\Validator;

const LP_FORM_ID = 'lp';

/** 自由記述の最大文字数 */
const LP_MESSAGE_MAX = 500;

/**
 * 希望時間帯。診療時間（site.php の schedule）と最終受付（終了の30分前）から組み立てる。
 *
 * @return array<string, string> id => 表示名
 */
function lp_time_slots(): array
{
    $lastEntry = (int) site('last_entry_minutes', 30);
    $opens = [];
    $lasts = [];
    foreach ((array) site('schedule', []) as $weekday => $day) {
        if (!is_array($day)) {
            continue;
        }
        $opens[] = $day['open'];
        $lasts[(int) $weekday] = date('H:i', (int) strtotime($day['close'] . " -{$lastEntry} minutes"));
    }
    if (!$opens) {
        return ['morning' => '午前', 'afternoon' => '午後', 'evening' => '夕方'];
    }
    $open = min($opens);
    $latest = max($lasts);
    $earlier = [];
    foreach ($lasts as $weekday => $time) {
        if ($time !== $latest) {
            $earlier[] = LP_WEEKDAYS[$weekday] . '曜は' . $time . 'まで';
        }
    }
    return [
        'morning' => "{$open}〜13:00",
        'afternoon' => '13:00〜16:00',
        'evening' => '16:00〜' . $latest . ($earlier ? '（' . implode('、', $earlier) . '）' : ''),
    ];
}

/** @return list<int> 休診の曜日（0=日曜 … 6=土曜） */
function lp_closed_weekdays(): array
{
    $closed = [];
    foreach ((array) site('schedule', []) as $weekday => $day) {
        if (!is_array($day)) {
            $closed[] = (int) $weekday;
        }
    }
    return $closed;
}

/**
 * Validator のフィールド定義
 *
 * @return array<string, array<string, mixed>>
 */
function lp_reservation_fields(): array
{
    return [
        'name' => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'max' => 50],
        'tel' => ['label' => '電話番号', 'type' => 'tel', 'required' => true],
        'email' => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true, 'max' => 254],
        'parts' => ['label' => '希望部位', 'type' => 'choices', 'options' => lp_part_options()],
        'date1' => ['label' => '第1希望日', 'type' => 'date', 'required' => true, 'min_days' => 1, 'max_days' => 60],
        'time1' => ['label' => 'ご希望の時間帯', 'type' => 'choice', 'required' => true, 'options' => lp_time_slots()],
        'message' => ['label' => 'ご質問など', 'type' => 'textarea', 'max' => LP_MESSAGE_MAX],
        'consent' => [
            'label' => '個人情報の取り扱い',
            'type' => 'consent',
            'required' => true,
            'message' => '個人情報の取り扱いへの同意が必要です。内容をご確認のうえ、チェックを入れてください。',
        ],
    ];
}

/**
 * 入力の検証（フィールド定義＋休診日のチェック）
 *
 * @param array<string, mixed> $input
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function lp_validate_reservation(array $input): array
{
    [$values, $errors] = (new Validator(lp_reservation_fields()))->validate($input);
    if (!isset($errors['date1']) && $values['date1'] !== '') {
        $weekday = (int) date('w', (int) strtotime($values['date1']));
        if (in_array($weekday, lp_closed_weekdays(), true)) {
            $errors['date1'] = LP_WEEKDAYS[$weekday] . '曜日は休診日です。別の日を選択してください。';
        } elseif (in_array($values['date1'], (array) site('closed_dates', []), true)) {
            $errors['date1'] = 'この日は祝日・年末年始のため休診です。別の日を選択してください。';
        }
    }
    return [$values, $errors];
}

/** 2026-10-08 → 2026年10月8日（木） */
function lp_format_date(string $ymd): string
{
    $time = strtotime($ymd);
    if ($time === false) {
        return $ymd;
    }
    return date('Y年n月j日', $time) . '（' . LP_WEEKDAYS[(int) date('w', $time)] . '）';
}

/**
 * 院内向けの通知メールと、申込者への自動返信メールを作る。
 * 自動返信には、申込者が自由に書ける値（お名前・ご質問など）を入れない。
 * 第三者のアドレスを入力して任意の文面を送らせる「踏み台」に使われるのを防ぐため。
 *
 * @param array<string, mixed> $values 検証済みの値
 * @return array{0: Mail, 1: Mail} [院内向け, 自動返信]
 */
function lp_reservation_mails(array $values): array
{
    $options = lp_part_options();
    $slots = lp_time_slots();
    $partIds = (array) $values['parts'];
    $parts = $partIds ? implode('、', array_map(static fn ($id) => $options[$id], $partIds)) : '未選択（カウンセリングで相談）';
    $date = lp_format_date((string) $values['date1']);
    $slot = $slots[(string) $values['time1']] ?? '';
    $name = (string) site('name');

    $estimate = '';
    if ($partIds) {
        $result = lp_estimate($partIds);
        $estimate = '（参考）希望部位の' . lp_plan()['course']['count'] . '回コース総額：' . tax_in($result['course'])
            . ($result['set'] ? '　※セット料金「' . $result['set']['label'] . '」で計算' : '') . "\n";
    }

    $adminBody = <<<TEXT
        医療脱毛LPから、カウンセリングの予約申し込みがありました。
        内容を確認し、申込者に日時を確定する連絡をしてください。

        ■ お名前：{$values['name']}
        ■ 電話番号：{$values['tel']}
        ■ メールアドレス：{$values['email']}
        ■ 希望部位：{$parts}
        ■ 第1希望日：{$date}
        ■ 時間帯：{$slot}
        ■ ご質問など：
        TEXT;
    $adminBody .= "\n" . ((string) $values['message'] !== '' ? (string) $values['message'] : 'なし') . "\n\n" . $estimate
        . '受付日時：' . date('Y-m-d H:i') . "\n";

    $replyBody = <<<TEXT
        ※このメールは送信専用のアドレスからお送りしています。

        {$name}です。
        医療レーザー脱毛のカウンセリング（無料）のご予約を受け付けました。

        ご予約はまだ確定していません。
        2診療日以内に、クリニックからお電話またはメールで、日時を確定するご連絡をいたします。

        ■ 受付内容
        希望部位：{$parts}
        第1希望日：{$date}
        時間帯：{$slot}

        ■ クリニック
        {$name}
        TEXT;
    $replyBody .= "\n〒" . site('address.postal_code') . ' ' . site('address.region') . site('address.locality') . site('address.street')
        . "\nTEL " . site('tel') . "\n";
    foreach (lp_hours_rows() as $row) {
        $replyBody .= '診療時間 ' . $row['days'] . ' ' . $row['time'] . "\n";
    }
    $replyBody .= '休診日 ' . site('closed_label') . "\n\n"
        . "このメールにお心当たりのない場合は、お手数ですが破棄してください。\n"
        . "（このメールは Web 制作のサンプルとして送信されたもので、実在の医療機関とは関係ありません）\n";

    $from = (string) site('mail_from');
    return [
        new Mail((string) site('mail_to', site('email')), '【医療脱毛LP】カウンセリングの予約申し込み', $adminBody, $from, (string) $values['email']),
        new Mail((string) $values['email'], "【{$name}】カウンセリングのご予約を受け付けました", $replyBody, $from, (string) site('email')),
    ];
}
