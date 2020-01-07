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

use Laminas\Diactoros\RequestTrait;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\RequestInterface;

/**
 * Implements methods for HTTP requests.
 *
 * Used by Cake\Http\Client to contain request information
 * for making requests.
 */
class Request extends Message implements RequestInterface
{
    use RequestTrait;

    /**
     * Constructor
     *
     * Provides backwards compatible defaults for some properties.
     *
     * @param string $url The request URL
     * @param string $method The HTTP method to use.
     * @param array $headers The HTTP headers to set.
     * @param array|string|null $data The request body to use.
     */
    public function __construct(string $url = '', string $method = self::METHOD_GET, array $headers = [], $data = null)
    {
        $this->setMethod($method);
        $this->uri = $this->createUri($url);
        $headers += [
            'Connection' => 'close',
            'User-Agent' => 'CakePHP',
        ];
        $this->addHeaders($headers);

        if ($data === null) {
            $this->stream = new Stream('php://memory', 'rw');
        } else {
            $this->setContent($data);
        }
    }

    /**
     * Add an array of headers to the request.
     *
     * @param array $headers The headers to add.
     * @return void
     */
    protected function addHeaders(array $headers): void
    {
        foreach ($headers as $key => $val) {
            $normalized = strtolower($key);
            $this->headers[$key] = (array)$val;
            $this->headerNames[$normalized] = $key;
        }
    }

    /**
     * Set the body/payload for the message.
     *
     * Array data will be serialized with Cake\Http\FormData,
     * and the content-type will be set.
     *
     * @param string|array $content The body for the request.
     * @return $this
     */
    protected function setContent($content)
    {
        if (is_array($content)) {
            $formData = new FormData();
            $formData->addMany($content);
            $this->addHeaders(['Content-Type' => $formData->contentType()]);
            $content = (string)$formData;
        }

        $stream = new Stream('php://memory', 'rw');
        $stream->write($content);
        $this->stream = $stream;

        return $this;
    }
}
