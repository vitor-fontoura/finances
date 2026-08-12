<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasTransactions;
use App\Types\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasTransactions;

    protected $fillable = [
        'team_id',
        'acct_id',
        'title',
        'type',
        'initial_balance',
        'currency',
        'fid',
        'due_day',
        'closing_day',
        'bank_id',
        'branch_id',
        'limit',
        'user_id',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
