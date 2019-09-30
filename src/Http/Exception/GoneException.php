<?php
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

/**
 * Represents an HTTP 410 error.
 */
class GoneException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    protected $_defaultCode = 410;

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Gone' will be the message
     * @param int $code Status code, defaults to 410
     * @param \Exception|null $previous The previous exception.
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (empty($message)) {
            $message = 'Gone';
        }
        parent::__construct($message, $code, $previous);
    }
}
