<?php
declare(strict_types=1);

namespace Cake\Http;

use Laminas\Diactoros\Uri as LaminasUri;

/**
 * The base and webroot properties have piggybacked on the Uri for
 * a long time. To preserve backwards compatibility and avoid dynamic
 * property errors in PHP 8.2 we use this subclass.
 */
class Uri extends LaminasUri
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
     * Create a subclassed Uri that includes additional CakePHP properties
     *
     * @param \Laminas\Diactoros\Uri $uri Uri instance to copy
     * @param string $base The base path.
     * @param string $webroot The webroot path.
     * @return self
     */
    public static function fromLaminas(LaminasUri $uri, string $base, string $webroot): self
    {
        $copy = (new self())
            ->withScheme($uri->getScheme())
            ->withHost($uri->getHost())
            ->withUserInfo($uri->getUserInfo())
            ->withPort($uri->getPort())
            ->withPath($uri->getPath())
            ->withQuery($uri->getQuery())
            ->withFragment($uri->getFragment());

        $copy->base = $base;
        $copy->webroot = $webroot;

        return $copy;
    }

    /**
     * Backwards compatible property read
     *
     * @param string $prop The property to read.
     * @return string|null
     */
    public function __get($prop): ?string
    {
        if ($prop !== 'base' && $prop !== 'webroot') {
            return null;
        }

        return $this->{$prop};
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
}
