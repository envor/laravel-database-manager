<?php

use Envor\DatabaseManager\Facades\DatabaseManager;

it('can manage', function () {
    foreach (config('database-manager.managers') as $driver => $manager) {
        $this->assertInstanceOf($manager, DatabaseManager::manage($driver));
    }
});

it('throws an exception for an invalid driver', function () {
    DatabaseManager::manage('invalid');
})->throws(Envor\DatabaseManager\Exceptions\InvalidDriverException::class);

it('can fake', function () {
    DatabaseManager::fake();

    DatabaseManager::manage('mysql');

    $this->assertInstanceOf(Envor\DatabaseManager\FakeDatabaseManager::class, DatabaseManager::getDatabaseManager());
});