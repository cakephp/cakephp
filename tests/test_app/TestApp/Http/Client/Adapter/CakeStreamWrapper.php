<?php
declare(strict_types=1);

namespace TestApp\Http\Client\Adapter;

use ArrayAccess;
use Exception;

class CakeStreamWrapper implements ArrayAccess
{
    private $stream;

    private $query = [];

    private $data = [
        'headers' => [
            'HTTP/1.1 200 OK',
        ],
    ];

    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        if ($path === 'http://throw_exception/') {
            throw new Exception();
        }

        $query = parse_url($path, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $this->query);
        }

        $this->stream = fopen('php://memory', 'rb+');
        fwrite($this->stream, str_repeat('x', 20000));
        rewind($this->stream);

        return true;
    }

    public function stream_close(): bool
    {
        return fclose($this->stream);
    }

    public function stream_read(int $count): string
    {
        if (isset($this->query['sleep'])) {
            sleep(1);
        }

        return fread($this->stream, $count);
    }

    public function stream_eof(): bool
    {
        return feof($this->stream);
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
