<?php
declare(strict_types=1);

namespace TestApp\Http\Client\Adapter;

use ArrayAccess;
use Exception;

class CakeStreamWrapper implements ArrayAccess
{
    private $_stream;

    private $_query = [];

    private $_data = [
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
            parse_str($query, $this->_query);
        }

        $this->_stream = fopen('php://memory', 'rb+');
        fwrite($this->_stream, str_repeat('x', 20000));
        rewind($this->_stream);

        return true;
    }

    public function stream_close(): bool
    {
        return fclose($this->_stream);
    }

    public function stream_read(int $count): string
    {
        if (isset($this->_query['sleep'])) {
            sleep(1);
        }

        return fread($this->_stream, $count);
    }

    public function stream_eof(): bool
    {
        return feof($this->_stream);
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->_data[$offset]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->_data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->_data[$offset]);
    }
}
