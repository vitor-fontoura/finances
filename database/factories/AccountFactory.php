<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'acct_id' => fake()->uuid(),
            'title' => fake()->company(),
            'type' => fake()->randomElement(['checking', 'savings', 'credit_card']),
            'initial_balance' => fake()->numberBetween(-100000, 100000),
            'currency' => 'BRL',
            'fid' => fake()->uuid(),
            'due_day' => fake()->optional()->numberBetween(1, 28),
            'closing_day' => fake()->optional()->numberBetween(1, 28),
            'bank_id' => fake()->optional()->numerify('###'),
            'branch_id' => fake()->optional()->numerify('####'),
            'limit' => fake()->optional()->numberBetween(1000, 50000),
            'user_id' => User::factory(),
        ];
    }
}
