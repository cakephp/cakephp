<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\Exception\Exception;

/**
 * Represents a fatal error
 */
class FatalErrorException extends Exception
{

    /**
     * Constructor
     *
     * @param string $message Message string.
     * @param int|null $code Code.
     * @param string|null $file File name.
     * @param int|null $line Line number.
     * @param \Exception|null $previous The previous exception.
     */
    public function __construct($message, $code = null, $file = null, $line = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }
}
