<?php
declare(strict_types=1);

namespace TestApp\Error\Exception;

use Exception;

/**
 * Exception that should generate a method name for custom exception rendering template
 * but the template `non_http_missing` does not exist.
 */
class NonHttpMissingException extends Exception
{
}
