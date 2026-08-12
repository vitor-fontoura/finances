<?php

declare(strict_types=1);

namespace App\Types;

use App\Types\Concerns\HasColors;
use App\Types\Concerns\HasLabels;
use App\Types\Contracts\ColoredContract;
use App\Types\Contracts\LabeledContract;

enum AccountType: string implements ColoredContract, LabeledContract
{
    use HasColors, HasLabels;

    case checking = 'checking';
    case credit_card = 'credit_card';
    case savings = 'savings';

    public function getColor(): string
    {
        return match ($this) {
            self::checking => 'blue',
            self::credit_card => 'indigo',
            self::savings => 'green',
        };
    }
}
