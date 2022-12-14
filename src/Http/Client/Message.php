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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

/**
 * Base class for other HTTP requests/responses
 *
 * Defines some common helper methods, constants
 * and properties.
 */
class Message
{
    /**
     * HTTP 200 code
     *
     * @var int
     */
    public const STATUS_OK = 200;

    /**
     * HTTP 201 code
     *
     * @var int
     */
    public const STATUS_CREATED = 201;

    /**
     * HTTP 202 code
     *
     * @var int
     */
    public const STATUS_ACCEPTED = 202;

    /**
     * HTTP 203 code
     *
     * @var int
     */
    public const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * HTTP 204 code
     *
     * @var int
     */
    public const STATUS_NO_CONTENT = 204;

    /**
     * HTTP 301 code
     *
     * @var int
     */
    public const STATUS_MOVED_PERMANENTLY = 301;

    /**
     * HTTP 302 code
     *
     * @var int
     */
    public const STATUS_FOUND = 302;

    /**
     * HTTP 303 code
     *
     * @var int
     */
    public const STATUS_SEE_OTHER = 303;

    /**
     * HTTP 307 code
     *
     * @var int
     */
    public const STATUS_TEMPORARY_REDIRECT = 307;

    /**
     * HTTP 308 code
     *
     * @var int
     */
    public const STATUS_PERMANENT_REDIRECT = 308;

    /**
     * HTTP GET method
     *
     * @var string
     */
    public const METHOD_GET = 'GET';

    /**
     * HTTP POST method
     *
     * @var string
     */
    public const METHOD_POST = 'POST';

    /**
     * HTTP PUT method
     *
     * @var string
     */
    public const METHOD_PUT = 'PUT';

    /**
     * HTTP DELETE method
     *
     * @var string
     */
    public const METHOD_DELETE = 'DELETE';

    /**
     * HTTP PATCH method
     *
     * @var string
     */
    public const METHOD_PATCH = 'PATCH';

    /**
     * HTTP OPTIONS method
     *
     * @var string
     */
    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * HTTP TRACE method
     *
     * @var string
     */
    public const METHOD_TRACE = 'TRACE';

    /**
     * HTTP HEAD method
     *
     * @var string
     */
    public const METHOD_HEAD = 'HEAD';

    /**
     * The array of cookies in the response.
     *
     * @var array
     */
    protected array $_cookies = [];

    /**
     * Get all cookies
     *
     * @return array
     */
    public function cookies(): array
    {
        return $this->_cookies;
    }
}
