<?php

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager as DatabaseManagerContract;
use Envor\DatabaseManager\Exceptions\InvalidDriverException;

class DatabaseManager
{
    public function manage(string $driver) : DatabaseManagerContract
    {
        if (! array_key_exists($driver, config('database-manager.managers'))) {
            throw new InvalidDriverException($driver);
        }

        $manager = config('database-manager.managers.' . $driver);

        return (new $manager);
    }
}
