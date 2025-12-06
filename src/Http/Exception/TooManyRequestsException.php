<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Exception;

use Throwable;

/**
 * Represents an HTTP 429 Too Many Requests error.
 */
class TooManyRequestsException extends HttpException
{
    /**
     * @inheritDoc
     */
    protected int $_defaultCode = 429;

    /**
     * Constructor
     *
     * @param string $message The error message
     * @param int|null $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = '', ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?: 'Too Many Requests', $code, $previous);
    }
}
