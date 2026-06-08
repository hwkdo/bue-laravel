<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('database.connections.custom-bue', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::connection('custom-bue')->create('test_table', function ($table) {
        $table->id();
        $table->string('name');
    });

    DB::connection('custom-bue')->table('test_table')->insert(['name' => 'test']);
});

it('uses default connection from config when no override is set', function () {
    $service = new BueLaravel;

    expect($service->connection())->toBe('testing');
});

it('uses custom connection via using()', function () {
    $service = (new BueLaravel)->using('custom-bue');

    expect($service->connection())->toBe('custom-bue');
});

it('table method queries the scoped connection', function () {
    $service = (new BueLaravel)->using('custom-bue');

    $result = $service->table('test_table')->where('name', 'test')->first();

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('test');
});

it('using returns new instance without mutating original', function () {
    $default = new BueLaravel;
    $custom = $default->using('custom-bue');

    expect($default->connection())->toBe('testing')
        ->and($custom->connection())->toBe('custom-bue');
});
