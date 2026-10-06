<?php

declare(strict_types=1);

namespace Core\Form;

/** 環境変数 MAIL_TRANSPORT=native のときだけ実際に送信し、それ以外はログに書く。 */
final class MailerFactory
{
    public static function create(): Mailer
    {
        return getenv('MAIL_TRANSPORT') === 'native'
            ? new NativeMailer()
            : new LogMailer(STORAGE_DIR . '/mail.log');
    }
}
