<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Types\ScheduleType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasTransactions;

    protected $fillable = [
        'team_id',
        'title',
        'matcher',
        'hidden',
        'type',
        'user_id',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
