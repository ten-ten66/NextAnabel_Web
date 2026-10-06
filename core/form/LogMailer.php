<?php

declare(strict_types=1);

namespace Core\Form;

/** 開発・デモ用。メールを送らずログファイルに追記する。 */
final class LogMailer implements Mailer
{
    public function __construct(private readonly string $file)
    {
    }

    public function send(Mail $mail): bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        $entry = sprintf(
            "==== %s ====\nTo: %s\nFrom: %s\nReply-To: %s\nSubject: %s\n\n%s\n\n",
            date('c'),
            $mail->to,
            $mail->from,
            $mail->replyTo ?? '-',
            $mail->subject,
            $mail->body
        );
        return file_put_contents($this->file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
