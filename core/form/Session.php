<?php

declare(strict_types=1);

namespace Core\Form;

/**
 * セッション開始。Cookie は JavaScript から読めず（HttpOnly）、
 * 他サイトからの遷移時には送られない（SameSite=Lax）設定にする。
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('sample_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** セッション固定攻撃への対策として、権限・状態が変わる時点でIDを振り直す。 */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
