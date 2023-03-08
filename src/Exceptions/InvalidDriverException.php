<?php

declare(strict_types=1);

namespace Envor\DatabaseManager\Exceptions;

use Exception;

class InvalidDriverException extends Exception
{
    public function __construct($driver)
    {
        parent::__construct("There is no manager configured to handle this $driver. Please create one or use a different driver.");
    }
}
