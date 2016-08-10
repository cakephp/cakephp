<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.7
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Exception;

/**
 * Represents an HTTP 503 error.
 */
class ServiceUnavailableException extends HttpException
{

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Service Unavailable' will be the message
     * @param int $code Status code, defaults to 503
     */
    public function __construct($message = null, $code = 503)
    {
        if (empty($message)) {
            $message = 'Service Unavailable';
        }
        parent::__construct($message, $code);
    }
}
