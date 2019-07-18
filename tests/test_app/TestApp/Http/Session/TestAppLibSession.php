<?php
namespace TestApp\Http\Session;

use SessionHandlerInterface;

/**
 * Test suite app/Http/Session session handler
 */
class TestAppLibSession implements SessionHandlerInterface
{

    public $options = [];

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function open($savePath, $name)
    {
        return true;
    }

    public function close()
    {
    }

    public function read($id)
    {
    }

    public function write($id, $data)
    {
    }

    public function destroy($id)
    {
    }

    public function gc($maxlifetime)
    {
    }
}
