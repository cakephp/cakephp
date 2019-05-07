<?php
declare(strict_types=1);

namespace TestApp\Error\Exception;

use Cake\Http\Exception\NotFoundException;

/**
 * Exception class for testing app error handlers and custom errors.
 */
class MissingWidgetThingException extends NotFoundException
{
}
