<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\BueLaravel;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

it('grants a bue role via admin statement', function () {
    mockAdminConnectionExpectingStatement('grant SOME_ROLE to ASKOPEC');

    expect(app(BueLaravel::class)->grantBueRole('ASKOPEC', 'SOME_ROLE'))->toBeTrue();
});

it('revokes a bue role via admin statement', function () {
    mockAdminConnectionExpectingStatement('revoke SOME_ROLE from ASKOPEC');

    expect(app(BueLaravel::class)->revokeBueRole('ASKOPEC', 'SOME_ROLE'))->toBeTrue();
});

it('disables a bue user via admin statement', function () {
    mockAdminConnectionExpectingStatement('alter user ASKOPEC account lock');

    expect(app(BueLaravel::class)->disableBueUser('ASKOPEC'))->toBeTrue();
});

it('enables a bue user via admin statement', function () {
    mockAdminConnectionExpectingStatement('alter user ASKOPEC account unlock');

    expect(app(BueLaravel::class)->enableBueUser('ASKOPEC'))->toBeTrue();
});

it('rejects invalid oracle identifiers', function (string $method, array $args) {
    expect(fn () => app(BueLaravel::class)->{$method}(...$args))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'grant invalid username' => ['grantBueRole', ['bad;drop', 'SOME_ROLE']],
    'grant invalid role' => ['grantBueRole', ['ASKOPEC', 'ROLE DROP']],
    'revoke invalid username' => ['revokeBueRole', ["ASKOPEC'", 'SOME_ROLE']],
    'disable invalid username' => ['disableBueUser', ['1ASKOPEC']],
    'enable invalid username' => ['enableBueUser', ['']],
    'get roles invalid username' => ['getBueRoles', ['user-name']],
]);

it('returns granted roles for a bue user', function () {
    DB::connection('testing')->getPdo()->exec(
        'CREATE TABLE IF NOT EXISTS DBA_ROLE_PRIVS (
            grantee TEXT NOT NULL,
            granted_role TEXT NOT NULL
        )'
    );
    DB::connection('testing')->table('DBA_ROLE_PRIVS')->delete();
    DB::connection('testing')->table('DBA_ROLE_PRIVS')->insert([
        ['grantee' => 'ASKOPEC', 'granted_role' => 'ROLE_A'],
        ['grantee' => 'ASKOPEC', 'granted_role' => 'ROLE_B'],
        ['grantee' => 'OTHER', 'granted_role' => 'ROLE_C'],
    ]);

    $roles = app(BueLaravel::class)->getBueRoles('ASKOPEC');

    expect($roles)->toHaveCount(2)
        ->and($roles->all())->toBe(['ROLE_A', 'ROLE_B']);
});

it('returns an empty collection when the bue user has no roles', function () {
    DB::connection('testing')->getPdo()->exec(
        'CREATE TABLE IF NOT EXISTS DBA_ROLE_PRIVS (
            grantee TEXT NOT NULL,
            granted_role TEXT NOT NULL
        )'
    );
    DB::connection('testing')->table('DBA_ROLE_PRIVS')->delete();

    expect(app(BueLaravel::class)->getBueRoles('ASKOPEC'))->toBeEmpty();
});

/**
 * @return MockInterface&Connection
 */
function mockAdminConnectionExpectingStatement(string $sql): MockInterface
{
    config()->set('bue-laravel.database.admin_connection', 'universalAdmin');

    /** @var MockInterface&Connection $connection */
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('statement')
        ->once()
        ->with($sql)
        ->andReturn(true);

    DB::shouldReceive('connection')
        ->once()
        ->with('universalAdmin')
        ->andReturn($connection);

    return $connection;
}
