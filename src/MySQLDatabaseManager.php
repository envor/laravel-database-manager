<?php

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager;
use Envor\DatabaseManager\Exceptions\NoConnectionSetException;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Stringable;

class MySQLDatabaseManager implements DatabaseManager
{
    /** @var string */
    protected $connection;

    protected function database(): Connection
    {
        if ($this->connection === null) {
            throw new NoConnectionSetException(static::class);
        }

        return DB::connection($this->connection);
    }

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function listTableNames(): array
    {
        // deprecated in Laravel 11
        if (method_exists($this->database(), 'getDoctrineSchemaManager')) {
            return $this->database()->getDoctrineSchemaManager()->listTableNames();
        }

        return $this->database()->getSchemaBuilder()->getTableListing();
    }

    public function createDatabase(string|Stringable $databaseName): bool
    {
        $databaseName = (string) $this->getDatabaseName((string) $databaseName);

        if ($this->databaseExists($databaseName)) {
            return false;
        }

        return $this->database()->getSchemaBuilder()->createDatabase($databaseName);
    }

    public function deleteDatabase(string|Stringable $databaseName, ?Carbon $deletedAt = null): bool
    {
        $databaseName = (string) $databaseName;

        try {
            $deletedAt = $deletedAt ?? now();
            $deletedDatabaseName = $this->getDatabaseName('deleted_'.$deletedAt->format('Y_m_d_H_i_s_').$databaseName);

            $this->database()->getSchemaBuilder()->dropDatabaseIfExists($deletedDatabaseName);
            $this->database()->statement("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';");
            $this->createDatabase($deletedDatabaseName);

            $currentConnection = $this->connection;

            config([
                'database.connections.'.$databaseName => $this->makeConnectionConfig(
                    baseConfig: config("database.connections.{$currentConnection}"),
                    databaseName: $deletedDatabaseName,
                ),
            ]);

            $this->setConnection($databaseName);

            $tables = $this->listTableNames();

            foreach ($tables as $table) {
                $this->database()->statement("CREATE TABLE if not exists {$deletedDatabaseName}.$table LIKE $table");
                $this->database()->statement("INSERT INTO {$deletedDatabaseName}.$table SELECT * FROM $table;");
            }

            $this->database()->getSchemaBuilder()->dropDatabaseIfExists($this->getDatabaseName($databaseName));
            $this->setConnection($currentConnection);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function eraseDatabase(string|Stringable $databaseName): bool
    {
        $databaseName = (string) $databaseName;

        try {
            $this->database()->statement("DROP DATABASE IF EXISTS `{$databaseName}`");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function cleanupOldDatabases(int $daysOld = 1): int
    {
        $count = 0;
        $this->getDeletedDatabases()->each(function ($databaseName) use ($daysOld, &$count) {
            $count += $this->deleteIfOld($databaseName, $daysOld);
        });

        return $count;
    }

    protected function deleteIfOld(string|Stringable $databaseName, int $daysOld = 1): int
    {
        $databaseName = (string) $databaseName;

        if ($this->databaseIsOld($databaseName, $daysOld)) {
            $this->eraseDatabase($databaseName);

            return 1;
        }

        return 0;
    }

    protected function getDeletedDatabases(): Collection
    {
        return collect($this->database()->select("show databases like 'deleted_%'"))
            ->map(fn ($database) => array_values(get_object_vars($database))[0]);
    }

    protected function databaseIsOld(string $deletedDatabaseName, int $daysOld = 1): bool
    {
        $dateSlice = array_slice(explode('_', $deletedDatabaseName), 1, 6);
        $dateString = implode('_', $dateSlice);
        $date = Carbon::createFromFormat('Y_m_d_H_i_s', $dateString)->getTimestamp();

        return $date < now()->subDays($daysOld)->getTimestamp();
    }

    public function databaseExists(string|Stringable $databaseName): bool
    {
        $databaseName = (string) $databaseName;

        return (bool) $this->database()->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'");
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $databaseName;

        return $baseConfig;
    }

    public function getDatabaseName(string|Stringable $databaseName): string
    {
        return (string) $databaseName;
    }

    public function __toString()
    {
        return 'mysql';
    }
}
