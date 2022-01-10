<?php
declare(strict_types=1);

namespace Cake\Tests\Benchmark;

use Cake\Routing\Route\Route;

class RoutingBench
{
    protected Route $route;

    public function __construct()
    {
    }

    public function setUpCompile()
    {
        $this->route = new Route('/{controller}/{action}/*');
    }

    /**
     * @BeforeMethods("setUpCompile")
     * @Revs(500)
     * @Iterations(5)
     */
    public function benchCompile()
    {
        $this->route->compile();
    }
}
