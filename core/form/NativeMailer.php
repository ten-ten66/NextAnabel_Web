<?php

declare(strict_types=1);

namespace Core\Form;

/**
 * PHP の mb_send_mail で送信する（UTF-8）。
 * 到達率を上げるには、送信元ドメインに SPF・DKIM・DMARC を設定すること。
 */
final class NativeMailer implements Mailer
{
    public function send(Mail $mail): bool
    {
        mb_language('uni');
        $headers = ['From' => $mail->from];
        if ($mail->replyTo !== null) {
            $headers['Reply-To'] = $mail->replyTo;
        }
        return mb_send_mail($mail->to, $mail->subject, $mail->body, $headers);
    }
}
