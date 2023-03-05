<?php
declare(strict_types=1);

namespace TestApp\Error\Exception;

use PDOException;

/**
 * Exception class for testing error page for PDOException.
 * This also emulates typical PDOException instances that return
 * strings for getCode().
 */
class MyPDOStringException extends PDOException
{
    public $queryString;

    public $params;

    /**
     * @inheritDoc
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->code = 'DB' . strval($code);
    }
}
