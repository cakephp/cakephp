<?php
namespace TestApp\Http;

use Cake\Core\HttpApplicationInterface;

class CustomApplication implements HttpApplicationInterface
{

    public function bootstrap()
    {
    }

    public function middleware($middleware)
    {
        return $middleware;
    }

    public function routes($routes)
    {
    }
}
