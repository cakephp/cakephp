<?php
declare(strict_types=1);

namespace TestApp\Error\Thing;

class DebuggableThing
{
    public function __debugInfo()
    {
        return ['foo' => 'bar', 'inner' => new self()];
    }
}
