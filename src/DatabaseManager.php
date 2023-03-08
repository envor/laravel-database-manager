<?php

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager as DatabaseManagerContract;
use Envor\DatabaseManager\Exceptions\InvalidDriverException;

class DatabaseManager
{
    public function manage(string $driver): DatabaseManagerContract
    {
        if (! array_key_exists($driver, config('database-manager.managers'))) {
            throw new InvalidDriverException($driver);
        }

        $manager = config('database-manager.managers.'.$driver);

        return $this->registerDatabaseManager($manager)->getDatabaseManager();
    }

    protected function registerDatabaseManager(string $manager)
    {
        // first we need to check if the driver is already bound
        if (app()->bound(DatabaseManagerContract::class)) {
            // if it is, we need to check if it's the same as the one we're trying to register
            // or if it's a fake
            $instance = app()->make(DatabaseManagerContract::class);
            if (
                $instance instanceof FakeDatabaseManager ||
                $instance instanceof $manager
            ) {
                // if it is, we don't need to do anything
                return $this;
            }

            // if it's not, we need to unbind it
            app()->forgetInstance(DatabaseManagerContract::class);
        }

        app()->bind(DatabaseManagerContract::class, $manager);

        return $this;
    }
    
    public function getDatabaseManager(): DatabaseManagerContract
    {
        return app(DatabaseManagerContract::class);
    }

    public function fake()
    {
        return $this->registerDatabaseManager(FakeDatabaseManager::class);
    }
}
