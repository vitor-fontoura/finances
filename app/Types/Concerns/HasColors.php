<?php

declare(strict_types=1);

namespace App\Types\Concerns;

use BackedEnum;
use Illuminate\Support\Str;

trait HasColors
{
    public static function getColors(): array
    {
        $key = is_subclass_of(static::class, BackedEnum::class) ? 'value' : 'name';

        return array_map(fn ($value) => [
            'label' => self::getColorFor($value),
            'value' => $value->$key,
        ], self::cases());
    }

    public static function getColorFor($value): ?string
    {
        if (empty($value)) {
            return '';
        }

        $snakeClassName = Str::snake(substr(strrchr(self::class, '\\'), 1));

        $translationKey = implode('.', [
            'types',
            $snakeClassName,
            $value->name,
        ]);

        return __($translationKey);
    }
}
