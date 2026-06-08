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

    $pdo->exec('DROP TABLE IF EXISTS intranet.betr_stamm');
    $pdo->exec(
        'CREATE TABLE intranet.betr_stamm (bnr INTEGER PRIMARY KEY, gewerbeamtuuid TEXT, formwerkvgn TEXT)'
    );
});

function insertBetrieb(int $bnr, ?string $gewerbeamtuuid = null, ?string $formwerkvgn = null): void
{
    DB::connection('testing')->table('intranet.betr_stamm')->insert([
        'bnr' => $bnr,
        'gewerbeamtuuid' => $gewerbeamtuuid,
        'formwerkvgn' => $formwerkvgn,
    ]);
}

it('finds betriebsnr via legacy gewerbeamtuuid', function () {
    insertBetrieb(71305341, '1234567');

    expect(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('1234567'))->toBe(71305341);
});

it('finds betriebsnr via formwerkvgn when gewerbeamtuuid is empty', function () {
    insertBetrieb(71305341, null, '7654321');

    expect(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('7654321'))->toBe(71305341);
});

it('finds betriebsnr via formwerkvgn when gewerbeamtuuid is a real uuid', function () {
    insertBetrieb(71305341, '550e8400-e29b-41d4-a716-446655440000', '7654321');

    expect(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('7654321'))->toBe(71305341);
});

it('prefers legacy gewerbeamtuuid over formwerkvgn for betriebsnr lookup', function () {
    insertBetrieb(71305341, '1234567', '7654321');

    expect(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('1234567'))->toBe(71305341)
        ->and(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('7654321'))->toBe(71305341);
});

it('returns null when no vorgangsnummer is stored', function () {
    insertBetrieb(71305341, '550e8400-e29b-41d4-a716-446655440000');

    expect(app(BueLaravel::class)->getBetriebsnrByVorgangsnummer('7654321'))->toBeNull();
});

it('resolves vorgangsnummer from legacy gewerbeamtuuid', function () {
    insertBetrieb(71305341, '1234567');

    expect(app(BueLaravel::class)->getVorgangsnummerByBetriebsnr(71305341))->toBe('1234567');
});

it('resolves vorgangsnummer from formwerkvgn when gewerbeamtuuid is a real uuid', function () {
    insertBetrieb(71305341, '550e8400-e29b-41d4-a716-446655440000', '7654321');

    expect(app(BueLaravel::class)->getVorgangsnummerByBetriebsnr(71305341))->toBe('7654321');
});

it('prefers legacy gewerbeamtuuid when resolving vorgangsnummer by betriebsnr', function () {
    insertBetrieb(71305341, '1234567', '7654321');

    expect(app(BueLaravel::class)->getVorgangsnummerByBetriebsnr(71305341))->toBe('1234567');
});
