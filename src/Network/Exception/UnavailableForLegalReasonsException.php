<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.2.12
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Exception;

/**
 * Represents an HTTP 451 error.
 *
 */
class UnavailableForLegalReasonsException extends HttpException
{

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Unavailable For Legal Reasons' will be the message
     * @param int $code Status code, defaults to 451
     */
    public function __construct($message = null, $code = 451)
    {
        if (empty($message)) {
            $message = 'Unavailable For Legal Reasons';
        }
        parent::__construct($message, $code);
    }
}
