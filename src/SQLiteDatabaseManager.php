<?php

namespace Envor\DatabaseManager;

use Envor\DatabaseManager\Contracts\DatabaseManager;
use Envor\DatabaseManager\Exceptions\NoConnectionSetException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Stringable;

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

    public function createDatabase(string|Stringable $databaseName): bool
    {
        $databaseName = (string) $databaseName;

        if ($this->databaseExists($databaseName)) {
            return false;
        }

        return $this->database()->getSchemaBuilder()->createDatabase($path = $this->getDatabaseName($databaseName));
    }

    public function deleteDatabase(string|Stringable $databaseName, ?Carbon $deletedAt = null): bool
    {
        $databaseName = (string) $databaseName;

        $deletedAt = $deletedAt ?? now();
        $deletedDatabaseFilePath = '.trash/'.$deletedAt->format('Y/m/d/H_i_s_').$databaseName.'.sqlite';

        $file = $this->storageDisk()->move($databaseName.'.sqlite', $deletedDatabaseFilePath);

        touch($this->storageDisk()->path($deletedDatabaseFilePath), $deletedAt->getTimestamp());

        return $file;
    }

    public function eraseDatabase(string|Stringable $databaseName): bool
    {
        $databaseName = (string) $databaseName;

        try {
            return $this->database()->getSchemaBuilder()->dropDatabaseIfExists($this->getDatabaseName($databaseName));
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function cleanupOldDatabases(int $daysOld = 1): int
    {
        $count = 0;
        // keep only files new than $daysOld days
        collect($this->storageDisk()->listContents('.trash', true))
            ->each(function ($file) use ($daysOld, &$count) {
                if ($file['type'] == 'file' && $file['lastModified'] < now()->subDays($daysOld)->getTimestamp()) {
                    $this->storageDisk()->delete($file['path']);
                    $count++;
                }
            });

        return $count;
    }

    public function listTableNames(): array
    {
        // deprecated in Laravel 11
        if (method_exists($this->database(), 'getDoctrineSchemaManager')) {
            return $this->database()->getDoctrineSchemaManager()->listTableNames();
        }

        return $this->database()->getSchemaBuilder()->getTableListing();
    }

    public function databaseExists(string $databaseName): bool
    {
        return File::exists($this->getDatabaseName($databaseName));
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
        return $this->storageDisk()->path($databaseName.'.sqlite');
    }

    protected function storageDisk(): Filesystem
    {
        $config = config('filesystems.disks.'.config('database-manager.sqlite_disk', 'local'));

        if ($config['driver'] != 'local') {
            throw new \Exception('Only local disk is supported for sqlite databases');
        }

        $disk = Storage::disk(config('database-manager.sqlite_disk', 'local'));

        return $disk;
    }

    public function __toString()
    {
        return 'sqlite';
    }
}
