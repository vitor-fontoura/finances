<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->word(),
            'matcher' => fake()->optional()->word(),
            'hidden' => fake()->boolean(20),
            'type' => fake()->randomElement(['any', 'income', 'expense']),
            'user_id' => User::factory(),
        ];
    }
}
