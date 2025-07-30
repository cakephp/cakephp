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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

/**
 * Exception for when a request failed.
 *
 * Examples:
 *
 *   - Request is invalid (e.g. method is missing)
 *   - Runtime request errors (e.g. the body stream is not seekable)
 */
class RequestException extends RuntimeException implements RequestExceptionInterface
{
    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected RequestInterface $request;

    /**
     * Constructor.
     *
     * @param string $message Exception message.
     * @param \Psr\Http\Message\RequestInterface $request Request instance.
     * @param \Throwable|null $previous Previous Exception
     */
    public function __construct(string $message, RequestInterface $request, ?Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, 0, $previous);
    }

    /**
     * Returns the request.
     *
     * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
