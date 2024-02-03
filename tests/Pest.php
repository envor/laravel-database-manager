<?php

use Envor\DatabaseManager\MySQLDatabaseManager;
use Envor\DatabaseManager\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function getDatabaseMysqlManager(string $conection): MySQLDatabaseManager
{
    return (new MySQLDatabaseManager)
        ->setConnection($conection);
}

function getProperty($object, $property)
{
    $reflection = (new \ReflectionClass($object))->getProperty($property);
    $reflection->setAccessible(true);

    return $reflection->getValue($object);
}
