<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasAccount;
use App\Concerns\HasCategory;
use App\Concerns\HasSchedule;
use App\Concerns\JoinsRelations;
use App\Types\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasAccount, HasCategory, HasFactory, HasSchedule, JoinsRelations;

    protected $fillable = [
        'team_id',
        'fitid',
        'account_id',
        'category_id',
        'schedule_id',
        'description',
        'amount',
        'date',
        'type',
        'origin',
        'user_id',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
