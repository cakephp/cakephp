<?php
namespace TestApp\Http;

use Cake\Http\BaseApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareApplication extends BaseApplication
{
    /**
     * @param \Cake\Http\MiddlewareStack $middleware The middleware stack to set in your App Class
     * @return \Cake\Http\MiddlewareStack
     */
    public function middleware($middleware)
    {
        $middleware
            ->push(function ($req, $res, $next) {
                $res = $res->withHeader('X-First', 'first');

                return $next($req, $res);
            })
            ->push(function ($req, $res, $next) {
                $res = $res->withHeader('X-Second', 'second');

                return $next($req, $res);
            })
            ->push(function ($req, $res, $next) {
                if ($req->hasHeader('X-pass')) {
                    $res = $res->withHeader('X-pass', $req->getHeaderLine('X-pass'));
                }
                $res = $res->withHeader('X-Second', 'second');

                return $next($req, $res);
            });

        return $middleware;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $request The response
     * @param callable $next The next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, $next)
    {
        return $res;
    }
}
