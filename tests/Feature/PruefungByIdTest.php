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

    $pdo->exec('DROP TABLE IF EXISTS mp.pruefungstermin');
    $pdo->exec('DROP TABLE IF EXISTS mp.pruefung');
    $pdo->exec('DROP TABLE IF EXISTS mp.ordnung');
    $pdo->exec('DROP TABLE IF EXISTS mp.handwerk');

    $pdo->exec('CREATE TABLE mp.handwerk (id INTEGER PRIMARY KEY, gewerbe TEXT)');
    $pdo->exec('CREATE TABLE mp.ordnung (id INTEGER PRIMARY KEY, handwerk_id INTEGER, fachbereich TEXT)');
    $pdo->exec('CREATE TABLE mp.pruefung (id INTEGER PRIMARY KEY, bezeichnung TEXT, ordnung_id INTEGER)');
    $pdo->exec('CREATE TABLE mp.pruefungstermin (pruefung_id INTEGER, lfdnr INTEGER, bezeichnung TEXT, von TEXT, bis TEXT)');
});

function insertPruefungByIdMitTermin(
    int $pruefungId,
    string $bezeichnung,
    string $gewerbe,
    string $fachbereich,
    string $terminVon,
    int $lfdnr = 1,
    ?string $terminBis = null,
    ?string $terminBezeichnung = null,
): void {
    DB::connection('testing')->table('mp.handwerk')->insertOrIgnore([
        'id' => $pruefungId,
        'gewerbe' => $gewerbe,
    ]);
    DB::connection('testing')->table('mp.ordnung')->insertOrIgnore([
        'id' => $pruefungId,
        'handwerk_id' => $pruefungId,
        'fachbereich' => $fachbereich,
    ]);
    DB::connection('testing')->table('mp.pruefung')->insertOrIgnore([
        'id' => $pruefungId,
        'bezeichnung' => $bezeichnung,
        'ordnung_id' => $pruefungId,
    ]);
    DB::connection('testing')->table('mp.pruefungstermin')->insert([
        'pruefung_id' => $pruefungId,
        'lfdnr' => $lfdnr,
        'bezeichnung' => $terminBezeichnung,
        'von' => $terminVon,
        'bis' => $terminBis,
    ]);
}

it('returns all termins for a pruefung by id', function () {
    insertPruefungByIdMitTermin(42, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-03-15 09:00:00', lfdnr: 1, terminBezeichnung: 'Teil I');
    insertPruefungByIdMitTermin(42, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-04-20 09:00:00', lfdnr: 2, terminBezeichnung: 'Teil II');
    insertPruefungByIdMitTermin(99, 'Maler TZ', 'Malerhandwerk', 'Farbe', '2025-03-15 09:00:00');

    $result = app(BueLaravel::class)->getPruefungById(42);

    expect($result)->toHaveCount(2)
        ->and($result->pluck('termin_bezeichnung')->all())->toBe(['Teil I', 'Teil II'])
        ->and($result->first()->gewerbe)->toBe('Tischlerhandwerk');
});

it('returns empty collection when pruefung id does not exist', function () {
    insertPruefungByIdMitTermin(1, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-03-15 09:00:00');

    $result = app(BueLaravel::class)->getPruefungById(404);

    expect($result)->toBeEmpty();
});
