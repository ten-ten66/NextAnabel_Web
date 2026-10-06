<?php

declare(strict_types=1);

namespace Core\Form;

interface Mailer
{
    public function send(Mail $mail): bool;
}
