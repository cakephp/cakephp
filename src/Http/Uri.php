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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Psr\Http\Message\UriInterface;
use UnexpectedValueException;

/**
 * The base and webroot properties have piggybacked on the Uri for
 * a long time. To preserve backwards compatibility and avoid dynamic
 * property errors in PHP 8.2 we use this implementation that decorates
 * the Uri from Laminas
 *
 * This class is an internal implementation workaround that will be removed in 5.x
 *
 * @internal
 */
class Uri implements UriInterface
{
    /**
     * @var string
     */
    private $base = '';

    /**
     * @var string
     */
    private $webroot = '';

    /**
     * @var \Psr\Http\Message\UriInterface
     */
    private $uri;

    /**
     * Constructor
     *
     * @param \Psr\Http\Message\UriInterface $uri Uri instance to decorate
     * @param string $base The base path.
     * @param string $webroot The webroot path.
     */
    public function __construct(UriInterface $uri, string $base, string $webroot)
    {
        $this->uri = $uri;
        $this->base = $base;
        $this->webroot = $webroot;
    }

    /**
     * Backwards compatibility shim for previously dynamic properties.
     *
     * @param string $name The attribute to read.
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'base' || $name === 'webroot') {
            return $this->{$name};
        }
        throw new UnexpectedValueException("Undefined property via __get('{$name}')");
    }

    /**
     * Get the decorated URI
     *
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Get the application base path.
     *
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * Get the application webroot path.
     *
     * @return string
     */
    public function getWebroot(): string
    {
        return $this->webroot;
    }

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        return $this->uri->getScheme();
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        return $this->uri->getAuthority();
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        return $this->uri->getUserInfo();
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->uri->getHost();
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        return $this->uri->getPort();
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->uri->getPath();
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->uri->getQuery();
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->uri->getFragment();
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        $new = clone $this;
        $new->uri = $this->uri->withScheme($scheme);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        $new = clone $this;
        $new->uri = $this->uri->withUserInfo($user, $password);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        $new = clone $this;
        $new->uri = $this->uri->withHost($host);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        $new = clone $this;
        $new->uri = $this->uri->withPort($port);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($path);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        $new = clone $this;
        $new->uri = $this->uri->withQuery($query);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        $new = clone $this;
        $new->uri = $this->uri->withFragment($fragment);

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->uri->__toString();
    }
}
