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
 * Represents an HTTP 406 error.
 */
class NotAcceptableException extends HttpException
{
    /**
     * @inheritDoc
     */
    protected int $_defaultCode = 406;

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Not Acceptable' will be the message
     * @param int|null $code Status code, defaults to 406
     * @param \Throwable|null $previous The previous exception.
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        if (!$message) {
            $message = 'Not Acceptable';
        }
        parent::__construct($message, $code, $previous);
    }
}
