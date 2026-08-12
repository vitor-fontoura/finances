<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'fitid' => fake()->optional()->uuid(),
            'account_id' => null,
            'category_id' => null,
            'first_amount' => fake()->optional()->numberBetween(-10000, 10000),
            'amount' => fake()->numberBetween(-10000, 10000),
            'start_date' => fake()->optional()->date(),
            'end_date' => fake()->optional()->date(),
            'installments' => fake()->optional()->numberBetween(1, 12),
            'matcher' => fake()->optional()->word(),
            'type' => fake()->randomElement(['expense', 'income']),
            'variant' => fake()->randomElement(['variable', 'fixed']),
            'user_id' => User::factory(),
        ];
    }
}
