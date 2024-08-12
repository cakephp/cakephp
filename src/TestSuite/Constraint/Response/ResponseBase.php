<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

use Cake\Http\Cookie\CookieCollection;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;
use Psr\Http\Message\ResponseInterface;

/**
 * Base constraint for response constraints
 *
 * @internal
 */
abstract class ResponseBase extends Constraint
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * Constructor
     *
     * @param \Psr\Http\Message\ResponseInterface|null $response Response
     */
    public function __construct(?ResponseInterface $response)
    {
        if (!$response) {
            throw new AssertionFailedError('No response set, cannot assert content.');
        }

        $this->response = $response;
    }

    /**
     * Get the response body as string
     *
     * @return string The response body.
     */
    protected function _getBodyAsString(): string
    {
        return (string)$this->response->getBody();
    }

    /**
     * Read a cookie from either the response cookie collection,
     * or headers
     *
     * @param string $name The name of the cookie you want to read.
     * @return array|null Null if the cookie does not exist, array with `value` as the only key.
     */
    protected function readCookie(string $name): ?array
    {
        if (method_exists($this->response, 'getCookie')) {
            return $this->response->getCookie($name);
        }
        $cookies = CookieCollection::createFromHeader($this->response->getHeader('Set-Cookie'));
        if (!$cookies->has($name)) {
            return null;
        }

        return $cookies->get($name)->toArray();
    }
}
