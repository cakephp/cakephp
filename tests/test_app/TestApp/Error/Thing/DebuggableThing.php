<?php
declare(strict_types=1);

namespace TestApp\Error\Thing;

class DebuggableThing
{
    /**
     * @inheritDoc
     */
    public function __debugInfo()
    {
        return ['foo' => 'bar', 'inner' => new self()];
    }
}
