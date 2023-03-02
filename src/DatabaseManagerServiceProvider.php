<?php

namespace Envor\DatabaseManager;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
// use Envor\DatabaseManager\Commands\DatabaseManagerCommand;

class DatabaseManagerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-database-manager');
            // ->hasConfigFile()
            // ->hasViews()
            // ->hasMigration('create_database_manager_table')
            // ->hasCommand(DatabaseManagerCommand::class);
    }
}
