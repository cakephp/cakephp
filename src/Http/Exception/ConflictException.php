<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Exception;

use Throwable;

/**
 * Represents an HTTP 409 error.
 */
class ConflictException extends HttpException
{
    /**
     * @inheritDoc
     */
    protected $_defaultCode = 409;

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Conflict' will be the message
     * @param int|null $code Status code, defaults to 409
     * @param \Throwable|null $previous The previous exception.
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Conflict';
        }
        parent::__construct($message, $code, $previous);
    }
}
