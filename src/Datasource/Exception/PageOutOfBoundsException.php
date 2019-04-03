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
namespace Cake\Datasource\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception raised when requested page number does not exist.
 */
class PageOutOfBoundsException extends Exception
{
    /**
     * {@inheritDoc}
     */
    protected $_messageTemplate = 'Page number %s could not be found.';

    /**
     * Constructor
     *
     * @param array|null $message Paging info.
     * @param int $code The code of the error, is also the HTTP status code for the error.
     * @param \Exception|null $previous The previous exception.
     */
    public function __construct($message = null, $code = 404, $previous = null)
    {
        parent::__construct($message, $code, $previous = null);
    }
}
