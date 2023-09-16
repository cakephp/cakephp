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

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

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
