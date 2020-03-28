<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Exception;

use Cake\Core\Exception\Exception;

/**
 * An exception subclass used by routing and application code to
 * trigger a redirect.
 *
 * The URL and status code are provided as constructor arguments.
 *
 * ```
 * throw new RedirectException('http://example.com/some/path', 301);
 * ```
 *
 * Additional headers can also be provided in the constructor, or
 * using the addHeaders() method.
 */
class RedirectException extends Exception
{
    /**
     * Headers to include in the response.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Constructor
     *
     * @param string $target The URL to redirect to.
     * @param int $code The exception code that will be used as a HTTP status code
     * @param array $headers The headers that should be sent in the unauthorized challenge response.
     */
    public function __construct(string $target, int $code = 302, array $headers = [])
    {
        parent::__construct($target, $code);
        $this->addHeaders($headers);
    }

    /**
     * Add headers to be included in the response generated from this exception
     *
     * @param array $headers An array of `header => value` to append to the exception.
     *  If a header already exists, the new values will be appended to the existing ones.
     * @return $this
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key][] = $value;
        }

        return $this;
    }

    /**
     * Remove a header from the exception.
     *
     * @param string $key The header to remove.
     * @return $this
     */
    public function removeHeader(string $key)
    {
        unset($this->headers[$key]);

        return $this;
    }

    /**
     * Get the headers from the exception.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
