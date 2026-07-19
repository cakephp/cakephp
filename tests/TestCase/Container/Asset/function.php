<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

function test(Bar $bar): Foo
{
    return new Foo($bar);
}
