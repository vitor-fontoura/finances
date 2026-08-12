<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('imports page loads without JavaScript errors', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $this->visit(route('imports', $team))
        ->assertSee('Importar')
        ->assertNoJavaScriptErrors();
});

test('importer is defined globally', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $this->visit(route('imports', $team))
        ->assertScript('typeof window.importer', 'function');
});
