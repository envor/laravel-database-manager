<?php

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager as DatabaseManagerContract;

class DatabaseManager
{
    public function manage(string $driver) : DatabaseManagerContract
    {
        $manager = config('database-manager.managers.' . $driver);

        return (new $manager);
    }
}
