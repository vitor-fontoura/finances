<?php

declare(strict_types=1);

namespace App\Types\Contracts;

interface ColoredContract
{
    public function getColor(): ?string;

    public static function getColorFor($value): ?string;

    public static function getColors(): array;
}
