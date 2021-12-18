<?php
declare(strict_types=1);

namespace TestApp\Error\Exception;

use PDOException;

/**
 * Exception class for testing error page for PDOException
 */
class MyPDOException extends PDOException
{
    public $queryString;

    public $params;
}
