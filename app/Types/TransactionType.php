<?php

declare(strict_types=1);

namespace App\Types;

use App\Types\Concerns\HasColors;
use App\Types\Concerns\HasLabels;
use App\Types\Concerns\InteractsWithArrays;
use App\Types\Contracts\ColoredContract;
use App\Types\Contracts\LabeledContract;

enum TransactionType: string implements ColoredContract, LabeledContract
{
    use HasColors, HasLabels, InteractsWithArrays;

    case income = 'income';
    case expense = 'expense';

    public function getColor(): string
    {
        return match ($this) {
            self::income => 'lime',
            self::expense => 'rose',
        };
    }
}
