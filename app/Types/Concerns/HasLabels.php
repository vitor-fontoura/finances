<?php

declare(strict_types=1);

namespace App\Types\Concerns;

use BackedEnum;
use Illuminate\Support\Str;

trait HasLabels
{
    /**
     * @return array<int, array{label: ?string, value: int|string}>
     */
    public static function getLabels(): array
    {
        return array_map(fn (BackedEnum $value) => [
            'label' => self::getLabelFor($value),
            'value' => $value->value,
        ], self::cases());
    }

    public function getLabel(): ?string
    {
        $snakeClassName = Str::snake(class_basename(self::class));

        return __(implode('.', [
            'types',
            $snakeClassName,
            $this->name,
        ]));
    }

    public function getShortLabel(): ?string
    {
        $snakeClassName = Str::snake(class_basename(self::class));

        return __(implode('.', [
            'types',
            $snakeClassName,
            $this->name.'_short',
        ]));
    }

    public static function getLabelFor(?BackedEnum $value): ?string
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
