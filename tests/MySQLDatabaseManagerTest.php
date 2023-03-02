<?php

use Envor\DatabaseManager\MySQLDatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Spatie\Docker\DockerContainer;

beforeEach(function () {
    if (! `which mysql`) {
        $this->fail('MySQL client is not installed');
    }

    if (! `which docker`) {
        $this->fail('Docker is not installed');
    }

    $this->containerInstance = DockerContainer::create('mariadb:latest')
        ->setEnvironmentVariable('MARIADB_ROOT_PASSWORD', 'root')
        ->setEnvironmentVariable('MARIADB_DATABASE', 'database_manager_tests')
        ->name('database_manager_tests')
        ->mapPort(10001, 3306)
        ->start();

    $i = 0;

    while ($i < 50) {
        $process = Process::run('mysql -u root -proot -P 10001 -h 127.0.0.1  database_manager_tests -e "show tables;"');
        if ($process->successful()) {
            break;
        }
        sleep(.5);
    }

    config(['database.connections.database_manager_tests' => array_merge(config('database.connections.mysql'), [
        'database' => 'database_manager_tests',
        'host' => '127.0.0.1',
        'port' => '10001',
        'username' => 'root',
        'password' => 'root',
    ])]);

    $this->databaseManager = getDatabaseManager();
    $this->databaseManager->createDatabase('test_database');
    $this->time = Carbon::now();
});

afterEach(function () {
    $this->containerInstance->stop();
});

function getDatabaseManager(): MySQLDatabaseManager
{
    return (new MySQLDatabaseManager)
        ->setConnection('database_manager_tests');
}

function getProperty($object, $property)
{
    $reflection = (new \ReflectionClass($object))->getProperty($property);
    $reflection->setAccessible(true);

    return $reflection->getValue($object);
}

it('can create a database', function () {
    expect($this->databaseManager->databaseExists('test_database'))->toBeTrue();
});

it('can delete a database without erasing it from disk', function () {
    expect($this->databaseManager->databaseExists('test_database'))->toBeTrue();

    $this->databaseManager->deleteDatabase('test_database', $this->time);

    expect($this->databaseManager->databaseExists('test_database'))->toBeFalse();
    expect($this->databaseManager->databaseExists('deleted_'.$this->time->format('Y_m_d_H_i_s_').'test_database'))->toBeTrue();
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
        'database' => 'test_database',
    ]));
});

it('can set a connection', function () {
    $connection = getProperty($this->databaseManager, 'connection');
    expect($connection)->toBe('database_manager_tests');
});

it('can get a list of table names', function () {
    DB::connection('database_manager_tests')
        ->statement('CREATE TABLE test_table (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))');

    expect($this->databaseManager->listTableNames())->toBe(['test_table']);
});

it('can erase a database from disk', function () {
    expect($this->databaseManager->databaseExists('test_database'))->toBeTrue();

    $this->databaseManager->eraseDatabase('test_database');

    expect($this->databaseManager->databaseExists('test_database'))->toBeFalse();
    expect($this->databaseManager->databaseExists('deleted_'.$this->time->format('Y_m_d_H_i_s_').'test_database'))->toBeFalse();
});

it('can cleanup old databases', function () {
    $threeDaysOld = $this->time->subDays(3);

    $this->databaseManager->deleteDatabase('test_database', $threeDaysOld);

    expect($this->databaseManager->databaseExists('deleted_'.$threeDaysOld->format('Y_m_d_H_i_s_').'test_database'))->toBeTrue();

    $this->databaseManager->cleanupOldDatabases(2);

    expect($this->databaseManager->databaseExists('deleted_'.$threeDaysOld->format('Y_m_d_H_i_s_').'test_database'))->toBeFalse();

    $this->databaseManager->createDatabase('test_database');

    $this->databaseManager->deleteDatabase('test_database', $this->time);

    expect($this->databaseManager->databaseExists('deleted_'.$this->time->format('Y_m_d_H_i_s_').'test_database'))->toBeTrue();

    $count = $this->databaseManager->cleanupOldDatabases(0);

    expect($count)->toBe(1);

    expect($this->databaseManager->databaseExists('deleted_'.$this->time->format('Y_m_d_H_i_s_').'test_database'))->toBeFalse();
});
