<?php
namespace TestPlugin\Http\Session;

use SessionHandlerInterface;

/**
 * Test suite plugin session handler
 */
class TestPluginSession implements SessionHandlerInterface
{

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
