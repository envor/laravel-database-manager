<?php

declare(strict_types=1);

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager;
use Envor\DatabaseManager\Exceptions\NoConnectionSetException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SQLiteDatabaseManager implements DatabaseManager
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

    public function createDatabase($databaseName): bool
    {
        try {
            return $this->databaseResolver()->put($databaseName.'.sqlite', '');
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function deleteDatabase($databaseName, $deletedAt = null): bool
    {
        $deletedAt = $deletedAt ?? now();
        $deletedDatabaseFilePath = '.trash/'.$deletedAt->format('Y/m/d/H_i_s_').$databaseName.'.sqlite';

        $file = $this->databaseResolver()->move($databaseName.'.sqlite', $deletedDatabaseFilePath);

        touch($this->databaseResolver()->path($deletedDatabaseFilePath), $deletedAt->getTimestamp());

        return $file;
    }

    public function eraseDatabase($databaseName): bool
    {
        try {
            return $this->databaseResolver()->delete($databaseName.'.sqlite');
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function cleanupOldDatabases(int $daysOld = 1): int
    {
        $count = 0;
        // keep only files new than $daysOld days
        collect($this->databaseResolver()->listContents('.trash', true))
            ->each(function ($file) use ($daysOld, &$count) {
                if ($file['type'] == 'file' && $file['lastModified'] < now()->subDays($daysOld)->getTimestamp()) {
                    $this->databaseResolver()->delete($file['path']);
                    $count++;
                }
            });

        return $count;
    }

    public function listTableNames(): array
    {
        return $this->database()->getDoctrineSchemaManager()->listTableNames();
    }

    public function databaseExists(string $databaseName): bool
    {
        return $this->databaseResolver()->exists($databaseName.'.sqlite');
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $this->getDatabaseName($databaseName);

        return $baseConfig;
    }

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getDatabaseName(string $databaseName): string
    {
        return $this->databaseResolver()->path($databaseName.'.sqlite');
    }

    protected function databaseResolver()
    {
        return Storage::disk(config('database_manager.sqlite_disk'));
    }

    public function __toString()
    {
        return 'sqlite';
    }
}
