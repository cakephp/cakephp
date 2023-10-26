<?php
declare(strict_types=1);

namespace Cake\Http;

use Laminas\Diactoros\Stream;

/**
 * Caching version of php://input
 *
 * Copied from laminas due to it being deprecated there.
 */
class PhpInputStream extends Stream
{
    private string $cache = '';

    private bool $reachedEof = false;

    /**
     * @param string|resource $stream Stream
     */
    public function __construct($stream = 'php://input')
    {
        parent::__construct($stream, 'r');
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if ($this->reachedEof) {
            return $this->cache;
        }

        $this->getContents();

        return $this->cache;
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function read($length): string
    {
        $content = parent::read($length);
        if (!$this->reachedEof) {
            $this->cache .= $content;
        }

        if ($this->eof()) {
            $this->reachedEof = true;
        }

        return $content;
    }

    /**
     * @param int $maxLength Max Length
     * @return string
     */
    public function getContents($maxLength = -1): string
    {
        if ($this->reachedEof) {
            return $this->cache;
        }

        $contents = stream_get_contents($this->resource, $maxLength);
        $this->cache .= $contents;

        if ($maxLength === -1 || $this->eof()) {
            $this->reachedEof = true;
        }

        return $contents;
    }
}
