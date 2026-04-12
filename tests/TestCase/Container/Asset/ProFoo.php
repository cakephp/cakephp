<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class ProFoo
{
    public $bar;

    public function __construct(?ProBar $bar = null)
    {
        $this->bar = $bar;
    }
}
