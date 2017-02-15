<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Exception;

use RuntimeException;

/**
 * Base class that all CakePHP Exceptions extend.
 */
class Exception extends RuntimeException
{

    /**
     * Array of attributes that are passed in from the constructor, and
     * made available in the view when a development error is displayed.
     *
     * @var array
     */
    protected $_attributes = [];

    /**
     * Template string that has attributes sprintf()'ed into it.
     *
     * @var string
     */
    protected $_messageTemplate = '';

    /**
     * Array of headers to be passed to Cake\Network\Response::header()
     *
     * @var array|null
     */
    protected $_responseHeaders = null;

    /**
     * Constructor.
     *
     * Allows you to create exceptions that are treated as framework errors and disabled
     * when debug = 0.
     *
     * @param string|array $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
     * @param int $code The code of the error, is also the HTTP status code for the error.
     * @param \Exception|null $previous the previous exception.
     */
    public function __construct($message, $code = 500, $previous = null)
    {
        if (is_array($message)) {
            $this->_attributes = $message;
            $message = vsprintf($this->_messageTemplate, $message);
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the passed in attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Get/set the response header to be used
     *
     * See also Cake\Network\Response::header()
     *
     * @param string|array|null $header An array of header strings or a single header string
     *  - an associative array of "header name" => "header value"
     *  - an array of string headers is also accepted
     * @param string|null $value The header value.
     * @return array
     */
    public function responseHeader($header = null, $value = null)
    {
        if ($header === null) {
            return $this->_responseHeaders;
        }
        if (is_array($header)) {
            return $this->_responseHeaders = $header;
        }
        $this->_responseHeaders = [$header => $value];
    }
}
