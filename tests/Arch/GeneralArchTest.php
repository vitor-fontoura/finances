<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Process;

describe('Global Arch QA', function () {

    arch('app')
        ->expect('App')
        ->toUseStrictTypes();

    arch('No debugs left behind')
        ->expect(['dd', 'dump', 'ray'])
        ->not->toBeUsed();

    arch('Every Controller has the "Controller" suffix')
        ->expect('App\Http\Controllers')
        ->toHaveSuffix('Controller')
        ->ignoring([
            'App\Http\Controllers\Concerns',
            'App\Http\Controllers\Contracts',
        ]);

    arch('Every Controller extends App\Http\Controllers\Controller')
        ->expect('App\Http\Controllers')
        ->toExtend('App\Http\Controllers\Controller')
        ->ignoring([
            'App\Http\Controllers\Concerns',
            'App\Http\Controllers\Contracts',
        ]);

    arch('All Contracts are actual interfaces')
        ->expect([
            'App\Models\Contracts',
            'App\Types\Contracts',
            'App\Scrapers\Contracts',
            'App\Services\Anticaptcha\Contracts',
            'App\Services\Common\Contracts',
            'App\Services\Common\Transporters\Contracts',
            'App\Services\Monolith\Contracts',
        ])
        ->toBeInterfaces();

    arch('All App\Traits are actual traits')
        ->expect('App\Traits')
        ->toBeTraits();

    arch('All Models should extend Eloquent\Model')
        ->expect('App\Models')
        ->toExtend('Illuminate\Database\Eloquent\Model')
        ->ignoring([
            'App\Concerns',
            'App\Models\Contracts',
        ]);

    arch('All Types should be enums')
        ->expect('App\Types')
        ->toBeEnums()
        ->ignoring([
            'App\Types\Contracts',
            'App\Types\Concerns',
        ]);

    arch('All Contracts should be interfaces')
        ->expect([
            'App\Contracts',
            'App\Types\Contracts',
        ])
        ->toBeInterfaces();

    arch('All Interfaces should have "Contract" suffix')
        ->expect('App')
        ->interfaces()
        ->toHaveSuffix('Contract');

    arch('All Concerns should be traits')
        ->expect([
            'App\Concerns',
            'App\Types\Concerns',
            'App\Http\Controllers\Concerns',
        ])
        ->toBeTraits();

    test('All Migrations should anonymously extend Illuminate\Migrations', function () {
        $files = glob(database_path('migrations').'/*.php');

        foreach ($files as $file) {
            $migration = require $file;

            expect($migration)->toBeInstanceOf(
                Migration::class
            );

            $reflection = new ReflectionClass($migration);
            expect($reflection->isAnonymous())->toBeTrue('Migration '.$reflection->getName().' in '.basename($file).' must be anonymous.');
        }
    });

    arch('All Factories should extend Illuminate\Factories')
        ->expect('Database\Factories')
        ->toExtend('Illuminate\Database\Eloquent\Factories\Factory');

    arch('Responses are forbidden outside controllers and middlewares')
        ->expect(['response'])
        ->toOnlyBeUsedIn(['App\Http\Controllers', 'App\Http\Middleware']);

});
