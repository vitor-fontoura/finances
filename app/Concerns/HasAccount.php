<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAccount
{
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
