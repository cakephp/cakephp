<?php
namespace TestApp\Http;

use Cake\Http\BaseApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BadResponseApplication extends BaseApplication
{
    /**
     * @param \Cake\Http\MiddlewareStack $middleware The middleware stack to set in your App Class
     * @return \Cake\Http\MiddlewareStack
     */
    public function middleware($middleware)
    {
        $middleware->add(function ($req, $res, $next) {
            return 'Not a response';
        });

        return $middleware;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $request The response
     * @param callable $next The next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        return $res;
    }
}
