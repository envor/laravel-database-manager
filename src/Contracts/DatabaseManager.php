<?php

namespace Envor\DatabaseManager\Contracts;

use Illuminate\Support\Carbon;
use Stringable;

interface DatabaseManager extends Stringable
{
    /**
     * Create a database.
     */
    public function createDatabase(string $databaseName): bool;

    /**
     * Delete a database.
     */
    public function deleteDatabase(string $databaseName, ?Carbon $deletedAt = null): bool;

    /**
     * Does a database exist.
     */
    public function databaseExists(string $databaseName): bool;

    /**
     * Make a DB connection config array.
     */
    public function makeConnectionConfig(array $baseConfig, string $databaseName): array;

    /**
     * Set a DB connection.
     *
     * @throws \Envor\DatabaseManager\Exceptions\NoConnectionSetException
     */
    public function setConnection(string $connection): self;

    /**
     * Get a list of table names.
     */
    public function listTableNames(): array;

    /**
     * Erase a database.
     */
    public function eraseDatabase(string $databaseName): bool;

    /**
     * Cleanup old databases.
     */
    public function cleanupOldDatabases(int $daysOld = 1): int;

    /**
     * Resolve the database name.
     */
    public function getDatabaseName(string $databaseName): string;
}
