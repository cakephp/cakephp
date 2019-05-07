<?php
declare(strict_types=1);

namespace TestApp\Http\Client\Adapter;

class CakeStreamWrapper implements \ArrayAccess
{
    private $_stream;

    private $_query = [];

    private $_data = [
        'headers' => [
            'HTTP/1.1 200 OK',
        ],
    ];

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        if ($path === 'http://throw_exception/') {
            throw new \Exception();
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

    public function stream_close()
    {
        return fclose($this->_stream);
    }

    public function stream_read($count)
    {
        if (isset($this->_query['sleep'])) {
            sleep(1);
        }

        return fread($this->_stream, $count);
    }

    public function stream_eof()
    {
        return feof($this->_stream);
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }
}
