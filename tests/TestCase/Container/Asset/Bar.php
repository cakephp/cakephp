<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class Bar implements BarInterface
{
    protected $something;

    public function setSomething($something): void
    {
        $this->something = $something;
    }
}
