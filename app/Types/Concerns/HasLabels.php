<?php

declare(strict_types=1);

namespace App\Types\Concerns;

use BackedEnum;
use Illuminate\Support\Str;

trait HasLabels
{
    public static function getLabels(): array
    {
        $key = is_subclass_of(static::class, BackedEnum::class) ? 'value' : 'name';

        return array_map(fn ($value) => [
            'label' => self::getLabelFor($value),
            'value' => $value->$key,
        ], self::cases());
    }

    public function getLabel(): ?string
    {
        $snakeClassName = Str::snake(substr(strrchr(self::class, '\\'), 1));

        return __(implode('.', [
            'types',
            $snakeClassName,
            $this->name,
        ]));
    }

    public function getShortLabel(): ?string
    {
        $snakeClassName = Str::snake(substr(strrchr(self::class, '\\'), 1));

        return __(implode('.', [
            'types',
            $snakeClassName,
            $this->name.'_short',
        ]));
    }

    public static function getLabelFor($value): ?string
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
