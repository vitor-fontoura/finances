<?php

declare(strict_types=1);

namespace App\Types\Contracts;

use BackedEnum;

interface LabeledContract
{
    public function getLabel(): ?string;

    public static function getLabelFor(?BackedEnum $value): ?string;

    /**
     * @return array<int, array{label: ?string, value: int|string}>
     */
    public static function getLabels(): array;
}
