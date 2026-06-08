<?php

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
}
