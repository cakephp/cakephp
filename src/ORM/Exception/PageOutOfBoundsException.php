<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception raised when requested page number does not exist.
 */
class PageOutOfBoundsException extends Exception
{
    /**
     * {@inheritDoc}
     */
    protected $_messageTemplate = 'Page number "%s" could not be found.';

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'Page not found.' will be the message
     * @param int $code Status code, defaults to 404
     */
    public function __construct($message = null, $code = 404)
    {
        parent::__construct($message, $code);
    }
}
