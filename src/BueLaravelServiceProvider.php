<?php

declare(strict_types=1);

namespace Hwkdo\BueLaravel;

use Hwkdo\BueLaravel\Commands\BueLaravelCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BueLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('bue-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_bue_laravel_table')
            ->hasCommand(BueLaravelCommand::class);
    }

    public function boot(): void
    {
        parent::boot();

        $adminConnection = config('bue-laravel.database.admin_connection');

        if (! is_string($adminConnection) || $adminConnection === '') {
            return;
        }

        // Host-App oder Tests können die Connection bereits definiert haben.
        if ($this->app['config']->has('database.connections.'.$adminConnection)) {
            return;
        }

        $this->app['config']->set(
            'database.connections.'.$adminConnection,
            config('bue-laravel.database.admin'),
        );
    }
}

