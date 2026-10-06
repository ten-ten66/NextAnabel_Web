<?php

declare(strict_types=1);

namespace Core\Form;

use DateTimeImmutable;

/**
 * フィールド定義に基づく入力検証と整形
 *
 * フィールド定義の例
 *   'name'    => ['label' => 'お名前', 'type' => 'text', 'required' => true, 'max' => 50],
 *   'kana'    => ['label' => 'フリガナ', 'type' => 'kana', 'required' => true],
 *   'email'   => ['label' => 'メールアドレス', 'type' => 'email', 'required' => true],
 *   'tel'     => ['label' => '電話番号', 'type' => 'tel', 'required' => true],
 *   'menu'    => ['label' => 'ご希望の施術', 'type' => 'choice', 'options' => ['ipl' => 'IPL光治療']],
 *   'areas'   => ['label' => '部位', 'type' => 'choices', 'options' => [...]],
 *   'date1'   => ['label' => '第1希望日', 'type' => 'date', 'min_days' => 1, 'max_days' => 60],
 *   'message' => ['label' => 'ご相談内容', 'type' => 'textarea', 'max' => 1000],
 *   'consent' => ['label' => '同意', 'type' => 'consent', 'required' => true, 'message' => '…'],
 *
 * textarea 以外の項目は改行・制御文字を受け付けない（メールヘッダインジェクション対策）。
 */
final class Validator
{
    /** @param array<string, array<string, mixed>> $fields */
    public function __construct(private readonly array $fields)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: array<string, string>} [整形済みの値, エラー文]
     */
    public function validate(array $input): array
    {
        $values = [];
        $errors = [];
        foreach ($this->fields as $name => $def) {
            [$value, $error] = $this->check($def, $input[$name] ?? null);
            $values[$name] = $value;
            if ($error !== null) {
                $errors[$name] = $error;
            }
        }
        return [$values, $errors];
    }

    /**
     * @param array<string, mixed> $def
     * @return array{0: mixed, 1: ?string}
     */
    private function check(array $def, mixed $raw): array
    {
        $label = (string) $def['label'];
        $type = (string) ($def['type'] ?? 'text');
        $required = (bool) ($def['required'] ?? false);

        if ($type === 'choices') {
            $allowed = array_map('strval', array_keys((array) ($def['options'] ?? [])));
            $picked = array_values(array_unique(array_filter(
                is_array($raw) ? $raw : [],
                static fn ($v) => is_string($v) && in_array($v, $allowed, true)
            )));
            return [$picked, ($required && !$picked) ? "{$label}を選択してください。" : null];
        }

        if ($type === 'consent') {
            $agreed = $raw === '1';
            $message = (string) ($def['message'] ?? "{$label}への同意が必要です。");
            return [$agreed ? '1' : '', ($required && !$agreed) ? $message : null];
        }

        $value = is_string($raw) ? $raw : '';
        if (!mb_check_encoding($value, 'UTF-8')) {
            return ['', "{$label}に使用できない文字が含まれています。"];
        }
        $value = $this->normalize($type, $value);

        if ($value === '') {
            if (!$required) {
                return ['', null];
            }
            $verb = in_array($type, ['choice', 'date'], true) ? '選択' : '入力';
            return ['', "{$label}を{$verb}してください。"];
        }

        $max = (int) ($def['max'] ?? ($type === 'textarea' ? 2000 : 100));
        if (mb_strlen($value) > $max) {
            return [$value, "{$label}は{$max}文字以内で入力してください。"];
        }

        if ($type !== 'textarea' && preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return [$value, "{$label}に改行や制御文字は使えません。"];
        }

        $error = match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'メールアドレスの形式が正しくありません。' : null,
            'tel' => $this->telError($value),
            'kana' => preg_match('/\A[ァ-ヶー・\x{3000} ]+\z/u', $value)
                ? null : "{$label}はカタカナで入力してください。",
            'choice' => in_array($value, array_map('strval', array_keys((array) ($def['options'] ?? []))), true)
                ? null : "{$label}を選択してください。",
            'date' => $this->dateError($label, $value, $def),
            default => null,
        };
        return [$value, $error];
    }

    private function normalize(string $type, string $value): string
    {
        // 改行コードを LF に統一し、NUL などの不可視文字を除去する（textarea のみ改行を残す）
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/\A[\s\x{3000}]+|[\s\x{3000}]+\z/u', '', $value);
        return match ($type) {
            'email' => mb_convert_kana($value, 'as'),
            'tel' => mb_convert_kana($value, 'as'),
            'kana' => mb_convert_kana($value, 'KVC'),
            'textarea' => (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value),
            default => $value,
        };
    }

    private function telError(string $value): ?string
    {
        $digits = (string) preg_replace('/\D/', '', $value);
        if (preg_match('/[^\d-]/', $value) || !preg_match('/\A0\d{9,10}\z/', $digits)) {
            return '電話番号は半角数字とハイフンで入力してください。';
        }
        return null;
    }

    /** @param array<string, mixed> $def */
    private function dateError(string $label, string $value, array $def): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return "{$label}の形式が正しくありません。";
        }
        $today = new DateTimeImmutable('today');
        $minDays = (int) ($def['min_days'] ?? 0);
        $maxDays = (int) ($def['max_days'] ?? 90);
        if ($date < $today->modify("+{$minDays} days")) {
            return $minDays > 0
                ? "{$label}は{$minDays}日後以降の日付を選択してください。"
                : "{$label}は本日以降の日付を選択してください。";
        }
        if ($date > $today->modify("+{$maxDays} days")) {
            return "{$label}は{$maxDays}日以内の日付を選択してください。";
        }
        return null;
    }
}
