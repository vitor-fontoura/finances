<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_datatable_view_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/');

        // Verify the user can access the app
        $response->assertStatus(200);
    }

    public function test_transaction_datatable_can_search_related_columns(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('transactions', ['current_team' => $user->currentTeam->slug]).'?s=category')
            ->assertSuccessful();
    }

    public function test_transaction_can_be_created(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $transaction = Transaction::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => $transaction->description,
        ]);
    }

    public function test_transaction_has_required_fields(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $transaction = Transaction::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($transaction->description);
        $this->assertNotNull($transaction->amount);
        $this->assertNotNull($transaction->date);
        $this->assertNotNull($transaction->type);
    }
}
