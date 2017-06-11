<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Exception;

/**
 * Wraps a PHP 7 Error object inside a normal Exception
 * so it can be handled correctly by the rest of the
 * error handling system
 */
class PHP7ErrorException extends Exception
{

    /**
     * The wrapped error object
     *
     * @var \Error
     */
    protected $_error;

    /**
     * Wraps the passed Error class
     *
     * @param \Error $error the Error object
     */
    public function __construct($error)
    {
        $this->_error = $error;
        $this->message = $error->getMessage();
        $this->code = $error->getCode();
        $this->file = $error->getFile();
        $this->line = $error->getLine();
        $msg = sprintf(
            '(%s) - %s in %s on %s',
            get_class($error),
            $this->message,
            $this->file ?: 'null',
            $this->line ?: 'null'
        );
        parent::__construct($msg, $this->code);
    }

    /**
     * Returns the wrapped error object
     *
     * @return \Error
     */
    public function getError()
    {
        return $this->_error;
    }
}
