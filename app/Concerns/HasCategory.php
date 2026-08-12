<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCategory
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
