<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $pdo = DB::connection('testing')->getPdo();

    try {
        $pdo->exec('ATTACH DATABASE ":memory:" AS intranet');
    } catch (PDOException) {
        // Schema already attached for this in-memory connection.
    }

    $pdo->exec('DROP TABLE IF EXISTS intranet.betr_person_quali');
    $pdo->exec('DROP TABLE IF EXISTS intranet.betr_personen');
    $pdo->exec('DROP TABLE IF EXISTS intranet.betr_gewerbe');
    $pdo->exec('DROP TABLE IF EXISTS intranet.betr_stamm');

    $pdo->exec(
        'CREATE TABLE intranet.betr_stamm (
            bnr TEXT PRIMARY KEY,
            name TEXT,
            betriebsanschrift TEXT,
            strasse TEXT,
            hausnummer TEXT,
            betr_plz TEXT,
            betr_ort TEXT,
            betriebsart TEXT,
            rechtsform TEXT,
            edat TEXT,
            betr_email TEXT,
            betr_telefon TEXT,
            hr_abt TEXT,
            hr_nummer TEXT,
            hr_gericht TEXT,
            hr_datum TEXT
        )'
    );

    $pdo->exec(
        'CREATE TABLE intranet.betr_gewerbe (
            betriebsnummer TEXT,
            betriebsart TEXT,
            gewerbe TEXT,
            gewerbename TEXT,
            spg TEXT,
            eintragungsdatum TEXT,
            teiltaetigkeit TEXT,
            eintragungsvoraussetzung TEXT
        )'
    );

    $pdo->exec(
        'CREATE TABLE intranet.betr_personen (
            betriebsnummer TEXT,
            personennummer TEXT,
            name TEXT,
            vorname TEXT,
            geburtsdatum TEXT,
            strasse TEXT,
            plz TEXT,
            ort TEXT,
            personhatstellung TEXT,
            geschlecht TEXT,
            anredekennung TEXT
        )'
    );

    $pdo->exec(
        'CREATE TABLE intranet.betr_person_quali (
            personennummer TEXT,
            status TEXT,
            gewerbe TEXT,
            gewerbe_bezeichnung TEXT,
            ausbildungsberechtigung TEXT,
            pruefungsdatum TEXT,
            pruefungsort TEXT,
            teiltaetigkeit TEXT,
            befristungsdatum TEXT,
            eintragungsvoraussetzung TEXT
        )'
    );
});

function seedBetriebSearchFixtures(): void
{
    DB::connection('testing')->table('intranet.betr_stamm')->insert([
        [
            'bnr' => '70001632',
            'name' => 'Heinrich Bodenhorn Stahlbau',
            'betriebsanschrift' => 'Feldsieper Str. 137a, 44809 Bochum',
            'strasse' => 'Feldsieper Str.',
            'hausnummer' => '137a',
            'betr_plz' => '44809',
            'betr_ort' => 'Bochum',
            'betriebsart' => 'Betrieb',
            'rechtsform' => 'Einzelfirma',
            'edat' => '30.04.1957',
            'betr_email' => 'info@bodenhorn.de',
            'betr_telefon' => '0234-510435',
            'hr_abt' => 'A',
            'hr_nummer' => '01165',
            'hr_gericht' => 'Bochum',
            'hr_datum' => '18.08.1965',
        ],
        [
            'bnr' => '70005263',
            'name' => 'Hans Jürgen Gerhardt Goldschmied',
            'betriebsanschrift' => 'Huestr 18, 44787 Bochum',
            'strasse' => 'Huestr',
            'hausnummer' => '18',
            'betr_plz' => '44787',
            'betr_ort' => 'Bochum',
            'betriebsart' => 'Betrieb',
            'rechtsform' => 'Alleininhaber',
            'edat' => '28.02.1968',
            'betr_email' => null,
            'betr_telefon' => null,
            'hr_abt' => null,
            'hr_nummer' => null,
            'hr_gericht' => null,
            'hr_datum' => null,
        ],
    ]);

    DB::connection('testing')->table('intranet.betr_gewerbe')->insert([
        [
            'betriebsnummer' => '70001632',
            'betriebsart' => 'Betrieb',
            'gewerbe' => '12130',
            'gewerbename' => 'Metallbauerhandwerk',
            'spg' => null,
            'eintragungsdatum' => '1970-08-28',
            'teiltaetigkeit' => null,
            'eintragungsvoraussetzung' => '§ 7.1a HwO-Meister',
        ],
    ]);

    DB::connection('testing')->table('intranet.betr_personen')->insert([
        [
            'betriebsnummer' => '70001632',
            'personennummer' => '7000163201',
            'name' => 'Samsel',
            'vorname' => 'Peter',
            'geburtsdatum' => '15.03.1962',
            'strasse' => 'Feldsieper Str. 137a',
            'plz' => '44809',
            'ort' => 'Bochum',
            'personhatstellung' => 'Inhaber',
            'geschlecht' => 'm',
            'anredekennung' => 'Herr',
        ],
        [
            'betriebsnummer' => '70005263',
            'personennummer' => '7000526301',
            'name' => 'Gerhardt',
            'vorname' => 'Hans Jürgen',
            'geburtsdatum' => '25.05.1954',
            'strasse' => null,
            'plz' => null,
            'ort' => null,
            'personhatstellung' => 'Inhaber',
            'geschlecht' => 'm',
            'anredekennung' => 'Herr',
        ],
    ]);

    DB::connection('testing')->table('intranet.betr_person_quali')->insert([
        [
            'personennummer' => '7000163201',
            'status' => 'aktiv',
            'gewerbe' => '12130',
            'gewerbe_bezeichnung' => 'Metallbauerhandwerk',
            'ausbildungsberechtigung' => null,
            'pruefungsdatum' => null,
            'pruefungsort' => null,
            'teiltaetigkeit' => null,
            'befristungsdatum' => null,
            'eintragungsvoraussetzung' => '§ 7.1a HwO-Meister',
        ],
    ]);
}

it('returns empty collection for short search queries', function () {
    seedBetriebSearchFixtures();

    expect(app(BueLaravel::class)->searchBetriebe('a'))->toBeEmpty();
});

it('finds betriebe by betriebsnummer', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('70001632');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70001632')
        ->and($results->first()->matched_on)->toContain('bnr');
});

it('finds betriebe when tokens match across different fields', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('Bodenhorn Bochum');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70001632')
        ->and($results->first()->matched_on)->toContain('name')
        ->and($results->first()->matched_on)->toContain('anschrift');
});

it('finds betriebe when tokens match name and person geburtsdatum', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('Gerhardt 25.05.1954');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70005263')
        ->and($results->first()->matched_on)->toContain('name')
        ->and($results->first()->matched_on)->toContain('person');
});

it('requires every token to match', function () {
    seedBetriebSearchFixtures();

    expect(app(BueLaravel::class)->searchBetriebe('Bodenhorn Dortmund'))->toBeEmpty();
});

it('finds betriebe by name', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('Bodenhorn');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70001632')
        ->and($results->first()->matched_on)->toContain('name');
});

it('finds betriebe by anschrift', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('Feldsieper');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70001632')
        ->and($results->first()->matched_on)->toContain('anschrift');
});

it('finds betriebe by personen geburtsdatum', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('25.05.1954');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70005263')
        ->and($results->first()->matched_on)->toContain('person');
});

it('finds betriebe by personen name', function () {
    seedBetriebSearchFixtures();

    $results = app(BueLaravel::class)->searchBetriebe('Samsel');

    expect($results)->toHaveCount(1)
        ->and($results->first()->bnr)->toBe('70001632')
        ->and($results->first()->matched_on)->toContain('person');
});

it('loads gewerbe for a betrieb', function () {
    seedBetriebSearchFixtures();

    $gewerbe = app(BueLaravel::class)->getBetriebGewerbeByBetriebsnr('70001632');

    expect($gewerbe)->toHaveCount(1)
        ->and($gewerbe->first()->gewerbename)->toBe('Metallbauerhandwerk');
});

it('loads personen for a betrieb', function () {
    seedBetriebSearchFixtures();

    $personen = app(BueLaravel::class)->getBetriebPersonenByBetriebsnr('70001632');

    expect($personen)->toHaveCount(1)
        ->and($personen->first()->name)->toBe('Samsel')
        ->and($personen->first()->personhatstellung)->toBe('Inhaber')
        ->and($personen->first()->qualifikationen)->toHaveCount(1)
        ->and($personen->first()->qualifikationen->first()->gewerbe_bezeichnung)->toBe('Metallbauerhandwerk')
        ->and($personen->first()->qualifikationen->first()->eintragungsvoraussetzung)->toBe('§ 7.1a HwO-Meister');
});

it('returns nested detail with gewerbe and personen', function () {
    seedBetriebSearchFixtures();

    $detail = app(BueLaravel::class)->getBetriebDetailByBetriebsnr('70001632');

    expect($detail)->not->toBeNull()
        ->and($detail->name)->toContain('Bodenhorn')
        ->and($detail->gewerbe)->toHaveCount(1)
        ->and($detail->personen)->toHaveCount(1)
        ->and($detail->personen->first()->qualifikationen)->toHaveCount(1);
});

it('loads person qualifikationen by personennummern', function () {
    seedBetriebSearchFixtures();

    $qualis = app(BueLaravel::class)->getPersonQualifikationenByPersonennummern(['7000163201']);

    expect($qualis)->toHaveCount(1)
        ->and($qualis->first()->gewerbe_bezeichnung)->toBe('Metallbauerhandwerk');
});

it('returns null detail for unknown betriebsnummer', function () {
    seedBetriebSearchFixtures();

    expect(app(BueLaravel::class)->getBetriebDetailByBetriebsnr('99999999'))->toBeNull();
});
