<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTransactions
{
    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
