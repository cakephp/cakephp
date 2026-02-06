<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class Baz
{
    public ?BarInterface $bar;

    public function __construct(?BarInterface $bar = null)
    {
        $this->bar = $bar;
    }
}
