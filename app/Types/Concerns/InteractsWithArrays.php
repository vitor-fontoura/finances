<?php

declare(strict_types=1);

namespace App\Types\Concerns;

trait InteractsWithArrays
{
    /**
     * @param  array<int, string>  $values
     * @return array<int, self>
     */
    public static function fromArray(array $values): array
    {
        return array_map(fn (string $value): self => static::from($value), $values);
    }
}
