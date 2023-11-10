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

use Cake\Event\Event;
use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Custom HTTP Client event to hold request and response data before
 * the HTTP client actually does the request.
 *
 * @template TSubject of \Cake\Http\Client
 * @extends \Cake\Event\Event<\Cake\Http\Client>
 */
class ClientEvent extends Event
{
    public RequestInterface $request;

    public ?array $options = null;

    public ?Response $response = null;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
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
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param \Cake\Http\Client\Response|null $response
     * @return $this
     */
    public function setResponse(?Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return \Cake\Http\Client\Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
