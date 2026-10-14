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

use Cake\Http\MessageTrait;
use Cake\Http\Stream;
use Cake\Http\Uri;
use Cake\Utility\Xml;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Uri\Rfc3986\Uri as Rfc3986Uri;

/**
 * Implements methods for HTTP requests.
 *
 * Used by Cake\Http\Client to contain request information
 * for making requests.
 */
class Request extends Message implements RequestInterface
{
    use MessageTrait;

    /**
     * The HTTP method.
     *
     * @var string
     */
    protected string $method = self::METHOD_GET;

    /**
     * The request target, if it has been explicitly set.
     *
     * @var string|null
     */
    protected ?string $requestTarget = null;

    /**
     * The request URI.
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected UriInterface $uri;

    /**
     * Constructor
     *
     * Provides backwards compatible defaults for some properties.
     *
     * @param \Psr\Http\Message\UriInterface|\Uri\Rfc3986\Uri|string $url The request URL
     * @param string $method The HTTP method to use.
     * @param array<non-empty-string, string|array<string>> $headers The HTTP headers to set.
     * @param array|string|null $data The request body to use.
     */
    public function __construct(
        UriInterface|Rfc3986Uri|string $url = '',
        string $method = self::METHOD_GET,
        array $headers = [],
        array|string|null $data = null,
    ) {
        $this->setMethod($method);
        $this->uri = $url instanceof UriInterface ? $url : new Uri($url);
        $headers += [
            'Connection' => 'close',
            'User-Agent' => ini_get('user_agent') ?: 'CakePHP',
        ];
        $this->addHeaders($headers);
        if (in_array($data, [null, '', []], true)) {
            $this->stream = new Stream('php://memory', 'rw');
        } else {
            $this->setContent($data);
        }
    }

    /**
     * Add an array of headers to the request.
     *
     * @param array<non-empty-string, string|array<string>> $headers The headers to add.
     * @return void
     */
    protected function addHeaders(array $headers): void
    {
        foreach ($headers as $key => $val) {
            $this->assertHeaderName($key);
            $this->replaceHeader($key, $this->filterHeaderValue($val));
        }
    }

    /**
     * Set the body/payload for the message.
     *
     * Array data will be serialized with {@link \Cake\Http\FormData},
     * and the content-type will be set.
     *
     * @param array|string $content The body for the request.
     * @return $this
     */
    protected function setContent(array|string $content): static
    {
        if (is_array($content)) {
            $contentType = $this->getHeaderLine('content-type');

            if (str_contains($contentType, 'application/json')) {
                $content = json_encode($content, JSON_THROW_ON_ERROR);
            } elseif (str_contains($contentType, 'application/xml')) {
                /** @phpstan-ignore-next-line */
                $content = (string)Xml::fromArray($content);
            } else {
                $formData = new FormData();
                $formData->addMany($content);

                /** @var array<non-empty-string, string|array<string>> $headers */
                $headers = ['Content-Type' => $formData->contentType()];
                $this->addHeaders($headers);
                $content = (string)$formData;
            }
        }

        $stream = new Stream('php://memory', 'rw');
        $stream->write($content);
        $this->stream = $stream;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target === '' ? '/' : $target;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace.');
        }

        return clone($this, ['requestTarget' => $requestTarget]);
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->setMethod($method);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone($this, ['uri' => $uri]);
        if (($preserveHost && $this->hasHeader('Host')) || $uri->getHost() === '') {
            return $new;
        }

        $host = $uri->getHost();
        if ($uri->getPort() !== null) {
            $host .= ':' . $uri->getPort();
        }
        $new->removeHeader('Host');
        $new->headerNames['host'] = 'Host';
        $new->headers['Host'] = [$host];

        return $new;
    }

    /**
     * Set and validate the HTTP method.
     *
     * @param string $method The HTTP method.
     * @return void
     * @throws \InvalidArgumentException When the method is not a valid token.
     */
    protected function setMethod(string $method): void
    {
        if (!preg_match('/^[!#$%&\'*+.^_`|~0-9a-z-]+$/iD', $method)) {
            throw new InvalidArgumentException(sprintf('Unsupported HTTP method `%s` provided.', $method));
        }
        $this->method = $method;
    }
}
