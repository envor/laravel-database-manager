<?php

use Envor\DatabaseManager\Facades\DatabaseManager;

it('can manage', function () {
    foreach (config('database-manager.managers') as $driver => $manager) {
        $this->assertInstanceOf($manager, DatabaseManager::manage($driver));
    }
});
