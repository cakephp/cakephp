<?php
namespace Cake\Core;

use Cake\Routing\RouteBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PluginApp implements ConsoleApplicationInterface, HttpApplicationInterface
{

    public function __construct(array $options = [])
    {
        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function routes($routes)
    {
        $bootstrap = __DIR__ . 'config' . DS . 'routes.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bootstrap()
    {
        $bootstrap = __DIR__ . 'config' . DS . 'bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function console($commands)
    {
        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function middleware($middleware)
    {
        return $middleware;
    }

    /**
     * Invoke the application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @param callable $next The next middleware
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        return $response;
    }
}
