<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ProBar implements BarInterface
{
    protected function __construct()
    {
    }

    public static function factory(): self
    {
        return new self();
    }
}
