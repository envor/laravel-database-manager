<?php

declare(strict_types=1);

namespace Envor\DatabaseManager;

use Carbon\Carbon;
use Envor\DatabaseManager\Contracts\DatabaseManager;
use Envor\DatabaseManager\Exceptions\NoConnectionSetException;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        return $this->database()->getDoctrineSchemaManager()->listTableNames();
    }

    public function createDatabase($databaseName): bool
    {
        $charset = $this->database()->getConfig('charset');
        $collation = $this->database()->getConfig('collation');

        return $this->database()->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET `$charset` COLLATE `$collation`");
    }

    public function deleteDatabase($databaseName, $deletedAt = null): bool
    {
        try {
            $deletedAt = $deletedAt ?? now();
            $deletedDatabaseName = 'deleted_'.$deletedAt->format('Y_m_d_H_i_s_').$databaseName;

            $this->database()->statement("DROP DATABASE IF EXISTS `{$deletedDatabaseName}`");
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

            $this->database()->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            $this->setConnection($currentConnection);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function eraseDatabase($databaseName): bool
    {
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

    protected function deleteIfOld(string $databaseName, int $daysOld = 1): int
    {
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

    public function databaseExists(string $databaseName): bool
    {
        return (bool) $this->database()->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'");
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $databaseName;

        return $baseConfig;
    }

    public function __toString()
    {
        return 'mysql';
    }
}
