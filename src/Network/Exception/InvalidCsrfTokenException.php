<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Exception;

/**
 * Represents an HTTP 403 error caused by an invalid CSRF token
 *
 */
class InvalidCsrfTokenException extends HttpException
{

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Invalid  CSRF Token' will be the message
     * @param int $code Status code, defaults to 403
     */
    public function __construct($message = null, $code = 403)
    {
        if (empty($message)) {
            $message = 'Invalid  CSRF Token';
        }
        parent::__construct($message, $code);
    }
}
