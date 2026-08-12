<?php

declare(strict_types=1);

namespace App\Types\Contracts;

interface LabeledContract
{
    public function getLabel(): ?string;

    public static function getLabelFor($value): ?string;

    public static function getLabels(): array;
}
