<?php
declare(strict_types=1);

namespace TestApp\Http;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareApplication extends BaseApplication
{
    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware stack to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(function ($req, $res, $next) {
                $res = $next($req, $res);

                return $res->withHeader('X-First', 'first');
            })
            ->add(function ($req, $res, $next) {
                $res = $next($req, $res);

                return $res->withHeader('X-Second', 'second');
            })
            ->add(function ($req, $res, $next) {
                $res = $next($req, $res);

                if ($req->hasHeader('X-pass')) {
                    $res = $res->withHeader('X-pass', $req->getHeaderLine('X-pass'));
                }

                return $res->withHeader('X-Second', 'second');
            });

        return $middlewareQueue;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $req): ResponseInterface
    {
        $res = new Response(['status' => 200]);

        return $res->withHeader('X-testing', 'source header');
    }
}
