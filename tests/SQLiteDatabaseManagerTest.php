<?php

use Envor\DatabaseManager\SQLiteDatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {

    Storage::fake('local');

    Storage::disk('local')->put('test_database.sqlite', '');

    config(['database.connections.database_manager_tests' => array_merge(config('database.connections.sqlite'), [
        'database' => Storage::disk('local')->path('test_database.sqlite'),
    ])]);

    $this->databaseManager = getSqliteDatabaseManager();
    $this->time = Carbon::now();
    $this->timeFormat = 'Y/m/d/H_i_s_';
    $this->deletedBasePath = '.trash/';
});

function getSqliteDatabaseManager(): SQLiteDatabaseManager
{
    return (new SQLiteDatabaseManager)
        ->setConnection('database_manager_tests');
}

it('can create a database', function () {
    $this->databaseManager->createDatabase('test_database_sqlite');
    expect($this->databaseManager->databaseExists('test_database_sqlite'))->toBeTrue();
});

it('can delete a database without erasing it from disk', function () {
    $this->databaseManager->deleteDatabase('test_database', $this->time);

    expect($this->databaseManager->databaseExists('test_database'))->toBeFalse();
    expect($this->databaseManager->databaseExists($this->deletedBasePath.$this->time->format($this->timeFormat).'test_database'))->toBeTrue();
});

it('can check if a database exists', function () {
    expect($this->databaseManager->databaseExists('test_database_two'))->toBeFalse();

    expect($this->databaseManager->databaseExists('test_database'))->toBeTrue();
});

it('can make a connection config', function () {
    $connectionConfig = $this->databaseManager->makeConnectionConfig(
        baseConfig: config('database.connections.database_manager_tests'),
        databaseName: 'test_database',
    );

    expect($connectionConfig)->toBe(array_merge(config('database.connections.database_manager_tests'), [
        'database' => Storage::disk('local')->path('test_database.sqlite'),
    ]));
});

it('can set a connection', function () {
    $connection = getProperty($this->databaseManager, 'connection');
    expect($connection)->toBe('database_manager_tests');
});

it('can get a list of table names', function () {
    DB::connection('database_manager_tests')
        // sqlite create table
        ->statement('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');

    expect($this->databaseManager->listTableNames())->toBe(['test_table']);
});

it('can erase a database from disk', function () {
    $this->databaseManager->eraseDatabase('test_database');

    expect($this->databaseManager->databaseExists('test_database'))->toBeFalse();
    expect($this->databaseManager->databaseExists($this->deletedBasePath.$this->time->format($this->timeFormat).'test_database'))->toBeFalse();
});

it('can cleanup old databases', function () {
    $threeDaysOld = $this->time->subDays(3);

    $this->databaseManager->deleteDatabase('test_database', $threeDaysOld);

    expect($this->databaseManager->databaseExists($this->deletedBasePath.$threeDaysOld->format($this->timeFormat).'test_database'))->toBeTrue();

    $this->databaseManager->cleanupOldDatabases(2);

    expect($this->databaseManager->databaseExists($this->deletedBasePath.$threeDaysOld->format($this->timeFormat).'test_database'))->toBeFalse();

    $this->databaseManager->createDatabase('test_database');

    $this->databaseManager->deleteDatabase('test_database', $this->time);

    expect($this->databaseManager->databaseExists($this->deletedBasePath.$this->time->format($this->timeFormat).'test_database'))->toBeTrue();

    $count = $this->databaseManager->cleanupOldDatabases(0);

    expect($count)->toBe(1);

    expect($this->databaseManager->databaseExists($this->deletedBasePath.$this->time->format($this->timeFormat).'test_database'))->toBeFalse();
});
