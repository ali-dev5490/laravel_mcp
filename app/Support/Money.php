<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function format(int $cents): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);

        return ($negative ? '-' : '').intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function toCents(float|int|string $major): int
    {
        return (int) round(((float) $major) * 100);
    }
}
