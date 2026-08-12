<?php

declare(strict_types=1);

namespace App\Types\Contracts;

use BackedEnum;

interface ColoredContract
{
    public function getColor(): ?string;

    public static function getColorFor(?BackedEnum $value): ?string;

    /**
     * @return array<int, array{label: ?string, value: int|string}>
     */
    public static function getColors(): array;
}
