<?php

declare(strict_types=1);

namespace App\Types\Concerns;

use BackedEnum;
use Illuminate\Support\Str;

trait HasColors
{
    /**
     * @return array<int, array{label: ?string, value: int|string}>
     */
    public static function getColors(): array
    {
        return array_map(fn (BackedEnum $value) => [
            'label' => self::getColorFor($value),
            'value' => $value->value,
        ], self::cases());
    }

    public static function getColorFor(?BackedEnum $value): ?string
    {
        if ($value === null) {
            return '';
        }

        $snakeClassName = Str::snake(class_basename(self::class));

        $translationKey = implode('.', [
            'types',
            $snakeClassName,
            $value->name,
        ]);

        return __($translationKey);
    }
}
