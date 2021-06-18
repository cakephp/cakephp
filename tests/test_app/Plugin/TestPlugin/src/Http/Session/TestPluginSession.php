<?php
declare(strict_types=1);

namespace TestPlugin\Http\Session;

use ReturnTypeWillChange;
use SessionHandlerInterface;

/**
 * Test suite plugin session handler
 */
class TestPluginSession implements SessionHandlerInterface
{
    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[ReturnTypeWillChange]
    public function read($id)
    {
    }

    public function write($id, $data): bool
    {
        return true;
    }

    public function destroy($id): bool
    {
        return true;
    }

    #[ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        return 0;
    }
}
