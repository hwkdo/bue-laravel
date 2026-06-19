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

    $pdo->exec('DROP TABLE IF EXISTS intranet.OLV_N8N');
    $pdo->exec(
        'CREATE TABLE intranet.OLV_N8N (onlineid TEXT PRIMARY KEY, name TEXT, status TEXT)'
    );
});

function insertOlvN8nRecord(string $onlineId, string $name, string $status = 'active'): void
{
    DB::connection('testing')->table('intranet.OLV_N8N')->insert([
        'onlineid' => $onlineId,
        'name' => $name,
        'status' => $status,
    ]);
}

it('returns olv record by onlineid', function () {
    insertOlvN8nRecord('12345', 'Musterbetrieb');

    $record = app(BueLaravel::class)->getOlvDataByOnlineId('12345');

    expect($record)->not->toBeNull()
        ->and($record->onlineid)->toBe('12345')
        ->and($record->name)->toBe('Musterbetrieb')
        ->and($record->status)->toBe('active');
});

it('returns null when olv record is not found', function () {
    expect(app(BueLaravel::class)->getOlvDataByOnlineId('99999'))->toBeNull();
});

it('finds olv record with numeric onlineid', function () {
    insertOlvN8nRecord('67890', 'Zweiter Betrieb');

    $record = app(BueLaravel::class)->getOlvDataByOnlineId(67890);

    expect($record)->not->toBeNull()
        ->and($record->onlineid)->toBe('67890');
});
