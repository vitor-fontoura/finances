<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasSchedule
{
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
