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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Stream;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * A live HTTP response body. Read errors are reported when consuming the body.
 *
 * @internal
 */
class ResponseStream extends Stream
{
    /**
     * @param resource $stream The open HTTP connection.
     * @param \Psr\Http\Message\RequestInterface $request The originating request.
     */
    public function __construct(mixed $stream, protected RequestInterface $request)
    {
        parent::__construct($stream);
    }

    /**
     * Live response bodies have no known size and cannot be replayed.
     *
     * @return null
     */
    public function getSize(): ?int
    {
        return null;
    }

    /**
     * Configure the timeout for individual read operations.
     *
     * @param float $seconds Timeout in seconds, or 0 for no timeout.
     * @return void
     */
    public function setReadTimeout(float $seconds): void
    {
        if (!is_finite($seconds) || $seconds < 0) {
            throw new InvalidArgumentException('readTimeout must be a finite non-negative number.');
        }
        $whole = $seconds === 0.0 ? -1 : (int)$seconds;
        $fraction = (int)(($seconds - (int)$seconds) * 1_000_000);
        if (!stream_set_timeout($this->getResource(), $whole, $fraction)) {
            throw new RuntimeException('Unable to set response read timeout.');
        }
    }

    /**
     * Decode gzip bytes incrementally, without buffering the response.
     *
     * @return void
     */
    public function decodeGzip(): void
    {
        $filter = stream_filter_append($this->getResource(), 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31]);
        if ($filter === false) {
            throw new RuntimeException('Unable to decode gzip response stream.');
        }
    }

    /**
     * @inheritDoc
     */
    public function read(int $length): string
    {
        try {
            $data = parent::read($length);
        } catch (RuntimeException $error) {
            $message = $this->getMetadata('timed_out')
                ? 'Response body read timed out.'
                : 'Error reading response body.';
            $this->close();

            throw new NetworkException($message, $this->request, $error);
        }
        if ($this->getMetadata('timed_out')) {
            $this->close();

            throw new NetworkException('Response body read timed out.', $this->request);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        $contents = '';
        while (!$this->eof()) {
            $contents .= $this->read(8192);
        }

        return $contents;
    }
}
