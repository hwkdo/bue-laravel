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

function insertPruefungMitTermin(
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

it('returns pruefungen matching gewerk and date range', function () {
    insertPruefungMitTermin(
        1,
        'Tischler TZ Nr. 1 Frühjahr 2025',
        'Tischlerhandwerk',
        'Holz',
        '2025-03-15 09:00:00',
        terminBis: '2025-03-15 16:00:00',
        terminBezeichnung: 'Teil I',
    );
    insertPruefungMitTermin(
        2,
        'Maler TZ Nr. 1 Frühjahr 2025',
        'Malerhandwerk',
        'Farbe',
        '2025-04-01 09:00:00',
    );

    $result = app(BueLaravel::class)->getPruefungsuebersicht('Tischler', '2025-03-01', '2025-05-01');

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe(1)
        ->and($result->first()->bezeichnung)->toBe('Tischler TZ Nr. 1 Frühjahr 2025')
        ->and($result->first()->gewerbe)->toBe('Tischlerhandwerk')
        ->and($result->first()->fachbereich)->toBe('Holz')
        ->and($result->first()->termin_bezeichnung)->toBe('Teil I');
});

it('excludes termins outside the date range', function () {
    insertPruefungMitTermin(1, 'Tischler Sommer', 'Tischlerhandwerk', 'Holz', '2025-06-01 09:00:00');
    insertPruefungMitTermin(2, 'Tischler Frühjahr', 'Tischlerhandwerk', 'Holz', '2025-03-15 09:00:00');

    $result = app(BueLaravel::class)->getPruefungsuebersicht('Tischler', '2025-03-01', '2025-05-01');

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe(2);
});

it('returns multiple termins for the same pruefung', function () {
    insertPruefungMitTermin(1, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-03-15 09:00:00', lfdnr: 1, terminBezeichnung: 'Teil I');
    insertPruefungMitTermin(1, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-04-20 09:00:00', lfdnr: 2, terminBezeichnung: 'Teil II');

    $result = app(BueLaravel::class)->getPruefungsuebersicht('Tischler', '2025-03-01', '2025-05-01');

    expect($result)->toHaveCount(2)
        ->and($result->pluck('termin_bezeichnung')->all())->toBe(['Teil I', 'Teil II']);
});

it('returns empty collection when no pruefungen match', function () {
    insertPruefungMitTermin(1, 'Maler TZ', 'Malerhandwerk', 'Farbe', '2025-03-15 09:00:00');

    $result = app(BueLaravel::class)->getPruefungsuebersicht('Tischler', '2025-03-01', '2025-05-01');

    expect($result)->toBeEmpty();
});

it('matches gewerk case-insensitively', function () {
    insertPruefungMitTermin(1, 'Tischler TZ', 'Tischlerhandwerk', 'Holz', '2025-03-15 09:00:00');

    $result = app(BueLaravel::class)->getPruefungsuebersicht('tischler', '2025-03-01', '2025-05-01');

    expect($result)->toHaveCount(1);
});
