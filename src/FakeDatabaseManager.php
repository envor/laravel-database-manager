<?php

declare(strict_types=1);

namespace Envor\DatabaseManager;

use Carbon\Carbon;
use Envor\DatabaseManager\Contracts\DatabaseManager;

class FakeDatabaseManager implements DatabaseManager
{
    public function createDatabase($databaseName): bool
    {
        return true;
    }

    public function deleteDatabase(string $databaseName, null|Carbon $deletedAt = null): bool
    {
        return true;
    }

    public function databaseExists(string $name): bool
    {
        return false;
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $baseConfig['driver'] === 'sqlite'
            ? ':memory:'
            : $databaseName;

        return $baseConfig;
    }

    public function setConnection(string $connection) : self
    {
        return $this;
    }

    /**
     * Get a list of table names.
     */
    public function listTableNames(): array
    {
        return [];
    }

    /**
     * Erase a database.
     */
    public function eraseDatabase(string $databaseName): bool
    {
        return true;
    }

    /**
     * Cleanup old databases.
     */
    public function cleanupOldDatabases(int $daysOld = 1): int
    {
        return 0;
    }

    public function __toString(): string
    {
        return 'FakeDatabaseManager';
    }
}