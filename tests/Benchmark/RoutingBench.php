<?php
declare(strict_types=1);

namespace Cake\Tests\Benchmark;

use Cake\Routing\Route\Route;

class RoutingBench
{
    public function __construct()
    {
    }

    /**
     * @Warmup(1)
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchCompile()
    {
        (new Route('/{controller}/{action}/*'))->compile();
    }
}
