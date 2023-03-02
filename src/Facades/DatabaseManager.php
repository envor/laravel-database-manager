<?php

namespace Envor\DatabaseManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Envor\DatabaseManager\DatabaseManager
 */
class DatabaseManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Envor\DatabaseManager\DatabaseManager::class;
    }
}
