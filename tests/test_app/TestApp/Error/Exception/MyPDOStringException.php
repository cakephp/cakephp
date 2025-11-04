<?php
declare(strict_types=1);

namespace TestApp\Error\Exception;

use PDOException;

/**
 * Custom PDO exception that returns string codes
 */
class MyPDOStringException extends PDOException
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = '', $code = 0)
    {
        parent::__construct($message, 0);
        $this->code = (string)$code;
    }
}
