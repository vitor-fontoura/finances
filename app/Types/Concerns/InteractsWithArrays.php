<?php

declare(strict_types=1);

namespace App\Types\Concerns;

trait InteractsWithArrays
{
    public static function fromArray(array $values): array
    {
        return array_map(fn ($value) => static::from($value), $values);
    }
}
