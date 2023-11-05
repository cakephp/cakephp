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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Used as a wrapper class to hold request and response data before
 * the HTTP client actually does the request.
 */
class ClientEvent
{
    public ?Response $response = null;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @param array $options
     */
    public function __construct(protected RequestInterface $request, protected array $options = [])
    {
    }

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return void
     */
    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param \Cake\Http\Client\Response|null $response
     * @return void
     */
    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    /**
     * @return \Cake\Http\Client\Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
