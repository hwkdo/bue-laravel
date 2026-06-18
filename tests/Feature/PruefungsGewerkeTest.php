<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $pdo = DB::connection('testing')->getPdo();

    try {
        $pdo->exec("ATTACH DATABASE ':memory:' AS mp");
    } catch (PDOException) {
        // Schema already attached for this in-memory connection.
    }

    $pdo->exec('DROP TABLE IF EXISTS mp.ordnung');
    $pdo->exec('DROP TABLE IF EXISTS mp.handwerk');

    $pdo->exec('CREATE TABLE mp.handwerk (id INTEGER PRIMARY KEY, gewerbe TEXT)');
    $pdo->exec('CREATE TABLE mp.ordnung (id INTEGER PRIMARY KEY, handwerk_id INTEGER)');
});

function insertOrdnungMitHandwerk(int $ordnungId, int $handwerkId, string $gewerbe): void
{
    DB::connection('testing')->table('mp.handwerk')->insertOrIgnore([
        'id' => $handwerkId,
        'gewerbe' => $gewerbe,
    ]);
    DB::connection('testing')->table('mp.ordnung')->insertOrIgnore([
        'id' => $ordnungId,
        'handwerk_id' => $handwerkId,
    ]);
}

it('returns distinct gewerke from ordnung handwerk relation', function () {
    insertOrdnungMitHandwerk(1, 10, 'Tischlerhandwerk');
    insertOrdnungMitHandwerk(2, 20, 'Malerhandwerk');
    insertOrdnungMitHandwerk(3, 10, 'Tischlerhandwerk');

    $result = app(BueLaravel::class)->getPruefungsGewerke();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('gewerbe')->all())->toBe(['Malerhandwerk', 'Tischlerhandwerk']);
});

it('returns gewerke sorted alphabetically', function () {
    insertOrdnungMitHandwerk(1, 10, 'Zimmererhandwerk');
    insertOrdnungMitHandwerk(2, 20, 'Bäcker-Handwerk');
    insertOrdnungMitHandwerk(3, 30, 'Augenoptikerhandwerk');

    $result = app(BueLaravel::class)->getPruefungsGewerke();

    expect($result->pluck('gewerbe')->all())->toBe([
        'Augenoptikerhandwerk',
        'Bäcker-Handwerk',
        'Zimmererhandwerk',
    ]);
});

it('excludes handwerk entries without gewerbe name', function () {
    DB::connection('testing')->table('mp.handwerk')->insert(['id' => 99, 'gewerbe' => null]);
    DB::connection('testing')->table('mp.ordnung')->insert(['id' => 1, 'handwerk_id' => 99]);
    insertOrdnungMitHandwerk(2, 10, 'Tischlerhandwerk');

    $result = app(BueLaravel::class)->getPruefungsGewerke();

    expect($result)->toHaveCount(1)
        ->and($result->first()->gewerbe)->toBe('Tischlerhandwerk');
});

it('includes handwerk id for each gewerk', function () {
    insertOrdnungMitHandwerk(1, 42, 'Tischlerhandwerk');

    $result = app(BueLaravel::class)->getPruefungsGewerke();

    expect($result->first()->id)->toBe(42)
        ->and($result->first()->gewerbe)->toBe('Tischlerhandwerk');
});
