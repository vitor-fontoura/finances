<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'fitid' => fake()->optional()->uuid(),
            'account_id' => null,
            'category_id' => null,
            'schedule_id' => null,
            'description' => fake()->sentence(),
            'amount' => fake()->numberBetween(-10000, 10000),
            'date' => fake()->date(),
            'type' => fake()->randomElement(['expense', 'income']),
            'origin' => fake()->randomElement(['manual', 'import']),
            'user_id' => User::factory(),
        ];
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn () => ['account_id' => $account->id]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => ['category_id' => $category->id]);
    }

    public function forSchedule(Schedule $schedule): static
    {
        return $this->state(fn () => ['schedule_id' => $schedule->id]);
    }
}
