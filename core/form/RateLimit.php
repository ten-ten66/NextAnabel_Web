<?php

declare(strict_types=1);

namespace Core\Form;

/** 送信間隔と1時間あたりの送信回数の制限（セッション単位） */
final class RateLimit
{
    private const KEY = '_rate';

    public function __construct(
        private readonly string $formId,
        private readonly int $minIntervalSec = 30,
        private readonly int $maxPerHour = 5,
    ) {
    }

    public function tooSoon(): bool
    {
        $hits = $this->recent();
        if ($hits && (time() - max($hits)) < $this->minIntervalSec) {
            return true;
        }
        return count($hits) >= $this->maxPerHour;
    }

    public function hit(): void
    {
        $hits = $this->recent();
        $hits[] = time();
        $_SESSION[self::KEY][$this->formId] = $hits;
    }

    /** @return list<int> */
    private function recent(): array
    {
        $hits = (array) ($_SESSION[self::KEY][$this->formId] ?? []);
        $since = time() - 3600;
        return array_values(array_filter($hits, static fn ($t) => is_int($t) && $t > $since));
    }
}
