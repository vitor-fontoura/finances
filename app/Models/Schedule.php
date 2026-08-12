<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasAccount;
use App\Concerns\HasCategory;
use App\Concerns\HasTransactions;
use App\Types\ScheduleType;
use App\Types\ScheduleVariant;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasAccount, HasCategory, HasFactory, HasTransactions;

    protected $fillable = [
        'team_id',
        'fitid',
        'title',
        'account_id',
        'category_id',
        'first_amount',
        'amount',
        'start_date',
        'end_date',
        'installments',
        'matcher',
        'type',
        'variant',
        'user_id',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'variant' => ScheduleVariant::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): ScheduleFactory
    {
        return ScheduleFactory::new();
    }
}
