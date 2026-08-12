<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        Category::factory()
            ->count(50)
            ->create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ]);
    }
}
