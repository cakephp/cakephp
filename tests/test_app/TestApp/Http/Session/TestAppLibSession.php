<?php
declare(strict_types=1);

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
