<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders transactions page with data', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Transaction::factory()->count(3)->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get("/{$team->slug}/transactions");

    $response->assertSuccessful();
    $response->assertSee('Transactions');
    $response->assertSee('Manage your transactions');
});

it('calls query method and returns data for current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $transaction = Transaction::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'description' => 'Test Transaction For Team',
    ]);

    $this->actingAs($user);

    $response = $this->get("/{$team->slug}/transactions");

    $response->assertSuccessful();
    $response->assertSee('Test Transaction For Team');
});

it('does not show data from other teams', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Transaction::factory()->create([
        'description' => 'Other Team Transaction',
    ]);

    $this->actingAs($user);

    $response = $this->get("/{$team->slug}/transactions");

    $response->assertSuccessful();
    $response->assertDontSee('Other Team Transaction');
});
