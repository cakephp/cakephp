<?php
declare(strict_types=1);

namespace TestPlugin\Http\Session;

use SessionHandlerInterface;

/**
 * Test suite plugin session handler
 */
class TestPluginSession implements SessionHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($id): string|false
    {
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc($max_lifetime): int|false
    {
        return 0;
    }
}
