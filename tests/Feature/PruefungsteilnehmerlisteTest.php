<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $pdo = DB::connection('testing')->getPdo();

    foreach (['mp', 'hwk'] as $schema) {
        try {
            $pdo->exec("ATTACH DATABASE ':memory:' AS {$schema}");
        } catch (PDOException) {
            // Schema already attached for this in-memory connection.
        }
    }

    $pdo->exec('DROP TABLE IF EXISTS mp.pruefling_mp');
    $pdo->exec('DROP TABLE IF EXISTS mp.handwerk');
    $pdo->exec('DROP TABLE IF EXISTS mp.pruefling_zu_prueftermin');
    $pdo->exec('DROP TABLE IF EXISTS mp.pruefung');
    $pdo->exec('DROP TABLE IF EXISTS mp.ordnung');
    $pdo->exec('DROP TABLE IF EXISTS hwk.person');

    $pdo->exec('CREATE TABLE mp.handwerk (id INTEGER PRIMARY KEY, gewerbe TEXT)');
    $pdo->exec('CREATE TABLE mp.ordnung (id INTEGER PRIMARY KEY, fachbereich TEXT)');
    $pdo->exec('CREATE TABLE mp.pruefung (id INTEGER PRIMARY KEY, ordnung_id INTEGER)');
    $pdo->exec('CREATE TABLE mp.pruefling_mp (id INTEGER PRIMARY KEY, handwerk_id INTEGER, person_id INTEGER)');
    $pdo->exec('CREATE TABLE mp.pruefling_zu_prueftermin (pruefling_id INTEGER, pruefung_id INTEGER)');
    $pdo->exec('CREATE TABLE hwk.person (id INTEGER PRIMARY KEY, name TEXT, vorname TEXT, geburtsdatum TEXT)');
});

function insertPruefungsteilnehmer(
    int $pruefungId,
    int $prueflingId,
    int $personId,
    string $name,
    string $vorname,
    ?string $geburtsdatum = null,
    string $gewerbe = 'Tischler',
    string $fachbereich = 'Holz',
): void {
    DB::connection('testing')->table('mp.handwerk')->insertOrIgnore(['id' => 1, 'gewerbe' => $gewerbe]);
    DB::connection('testing')->table('mp.ordnung')->insertOrIgnore(['id' => 1, 'fachbereich' => $fachbereich]);
    DB::connection('testing')->table('mp.pruefung')->insertOrIgnore([
        'id' => $pruefungId,
        'ordnung_id' => 1,
    ]);
    DB::connection('testing')->table('hwk.person')->insertOrIgnore([
        'id' => $personId,
        'name' => $name,
        'vorname' => $vorname,
        'geburtsdatum' => $geburtsdatum,
    ]);
    DB::connection('testing')->table('mp.pruefling_mp')->insertOrIgnore([
        'id' => $prueflingId,
        'handwerk_id' => 1,
        'person_id' => $personId,
    ]);
    DB::connection('testing')->table('mp.pruefling_zu_prueftermin')->insert([
        'pruefling_id' => $prueflingId,
        'pruefung_id' => $pruefungId,
    ]);
}

it('returns pruefungsteilnehmer for a given pruefung id', function () {
    insertPruefungsteilnehmer(14634, 1, 100, 'Müller', 'Hans', '1990-05-15');
    insertPruefungsteilnehmer(14634, 2, 101, 'Schmidt', 'Anna', '1985-03-20');

    $result = app(BueLaravel::class)->getPruefungsteilnehmerliste(14634);

    expect($result)->toHaveCount(2)
        ->and($result->first()->test)->toBe(14634)
        ->and($result->first()->mpname)->toBe('Müller')
        ->and($result->first()->mpvname)->toBe('Hans')
        ->and($result->first()->mpgebdat)->toBe('1990-05-15')
        ->and($result->first()->gewerbe)->toBe('Tischler')
        ->and($result->first()->fachbereich)->toBe('Holz');
});

it('returns only teilnehmer of the requested pruefung', function () {
    insertPruefungsteilnehmer(14634, 1, 100, 'Müller', 'Hans');
    insertPruefungsteilnehmer(99999, 2, 101, 'Schmidt', 'Anna');

    $result = app(BueLaravel::class)->getPruefungsteilnehmerliste(14634);

    expect($result)->toHaveCount(1)
        ->and($result->first()->mpname)->toBe('Müller');
});

it('returns empty collection when no teilnehmer exist', function () {
    $result = app(BueLaravel::class)->getPruefungsteilnehmerliste(14634);

    expect($result)->toBeEmpty();
});

it('deduplicates teilnehmer via distinct', function () {
    insertPruefungsteilnehmer(14634, 1, 100, 'Müller', 'Hans');

    DB::connection('testing')->table('mp.pruefling_zu_prueftermin')->insert([
        'pruefling_id' => 1,
        'pruefung_id' => 14634,
    ]);

    $result = app(BueLaravel::class)->getPruefungsteilnehmerliste(14634);

    expect($result)->toHaveCount(1);
});
