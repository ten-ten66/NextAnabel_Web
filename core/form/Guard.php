<?php

declare(strict_types=1);

namespace Core\Form;

/**
 * 機械的な投稿の検出
 *   - ハニーポット: 人には見えない入力欄に値が入っていたらボットとみなす
 *   - 入力時間: フォーム表示から数秒以内の送信はボットとみなす
 * 検出しても利用者側には成功したように見せ、ボットに判定基準を学習させない。
 */
final class Guard
{
    public const HONEYPOT = 'website';
    private const KEY = '_form_rendered_at';

    public static function stamp(string $formId): void
    {
        $_SESSION[self::KEY][$formId] = time();
    }

    /** @param array<string, mixed> $input */
    public static function isBot(array $input, string $formId, int $minSeconds = 3): bool
    {
        if (trim((string) ($input[self::HONEYPOT] ?? '')) !== '') {
            return true;
        }
        $renderedAt = $_SESSION[self::KEY][$formId] ?? null;
        return is_int($renderedAt) && (time() - $renderedAt) < $minSeconds;
    }
}
