<?php
declare(strict_types=1);

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
namespace Cake\Core\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class that all CakePHP Exceptions extend.
 *
 * @method int getCode()
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
     * Array of headers to be passed to Cake\Http\Response::header()
     *
     * @var array|null
     */
    protected $_responseHeaders;

    /**
     * Default exception code
     *
     * @var int
     */
    protected $_defaultCode = 500;

    /**
     * Constructor.
     *
     * Allows you to create exceptions that are treated as framework errors and disabled
     * when debug mode is off.
     *
     * @param string|array $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
     * @param int|null $code The code of the error, is also the HTTP status code for the error.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct($message = '', ?int $code = null, ?Throwable $previous = null)
    {
        if ($code === null) {
            $code = $this->_defaultCode;
        }

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
    public function getAttributes(): array
    {
        return $this->_attributes;
    }

    /**
     * Get/set the response header to be used
     *
     * See also Cake\Http\Response::withHeader()
     *
     * @param string|array|null $header A single header string or an associative
     *   array of "header name" => "header value"
     * @param string|null $value The header value.
     * @return array|null
     */
    public function responseHeader($header = null, $value = null): ?array
    {
        if ($header === null) {
            return $this->_responseHeaders;
        }
        if (is_array($header)) {
            return $this->_responseHeaders = $header;
        }

        return $this->_responseHeaders = [$header => $value];
    }
}
