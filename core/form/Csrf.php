<?php

declare(strict_types=1);

namespace Core\Form;

/** CSRF トークン（セッションに保存し、送信時に定数時間比較で照合する） */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function verify(mixed $token): bool
    {
        $expected = $_SESSION[self::KEY] ?? '';
        return is_string($token) && is_string($expected) && $expected !== ''
            && hash_equals($expected, $token);
    }

    /** 送信完了後に破棄し、同じフォームの再送信を無効にする。 */
    public static function rotate(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
