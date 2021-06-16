<?php
declare(strict_types=1);

namespace TestApp\Http\Session;

use ReturnTypeWillChange;
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
