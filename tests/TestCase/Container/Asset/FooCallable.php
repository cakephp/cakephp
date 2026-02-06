<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class FooCallable
{
    public function __invoke(Bar $bar): Foo
    {
        return new Foo($bar);
    }
}
