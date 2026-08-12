<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAccount
{
    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
