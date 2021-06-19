<?php
declare(strict_types=1);

namespace TestPlugin\Http\Session;

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

    public function read($id): string|false
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

    public function gc($maxlifetime): int|false
    {
        return 0;
    }
}
