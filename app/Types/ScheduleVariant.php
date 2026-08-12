<?php

declare(strict_types=1);

namespace App\Types;

use App\Types\Concerns\HasColors;
use App\Types\Concerns\HasLabels;
use App\Types\Contracts\ColoredContract;
use App\Types\Contracts\LabeledContract;

enum ScheduleVariant: string implements ColoredContract, LabeledContract
{
    use HasColors, HasLabels;

    case variable = 'variable';
    case fixed = 'fixed';

    public function getColor(): string
    {
        return match ($this) {
            self::variable => 'orange',
            self::fixed => 'violet',
        };
    }
}
