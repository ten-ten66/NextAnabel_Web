<?php

declare(strict_types=1);

namespace Core\Form;

use InvalidArgumentException;

/** 送信するメール1通。ヘッダに使う値に改行が含まれていたら生成時点で拒否する。 */
final class Mail
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $from,
        public readonly ?string $replyTo = null,
    ) {
        foreach ([$to, $subject, $from, (string) $replyTo] as $header) {
            if (preg_match('/[\r\n]/', $header)) {
                throw new InvalidArgumentException('メールヘッダに改行は使えません。');
            }
        }
    }
}
