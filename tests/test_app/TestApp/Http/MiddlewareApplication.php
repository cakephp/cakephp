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
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(function ($request, $handler) {
                return $handler->handle($request)->withHeader('X-First', 'first');
            })
            ->add(function ($request, $handler) {
                return $handler->handle($request)->withHeader('X-Second', 'second');
            })
            ->add(function ($request, $handler) {
                $response = $handler->handle($request);

                if ($request->hasHeader('X-pass')) {
                    $response = $response->withHeader('X-pass', $request->getHeaderLine('X-pass'));
                }

                return $response->withHeader('X-Second', 'second');
            });

        return $middlewareQueue;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     */
    public function handle(ServerRequestInterface $req): ResponseInterface
    {
        $res = new Response(['status' => 200]);

        return $res->withHeader('X-testing', 'source header');
    }
}
