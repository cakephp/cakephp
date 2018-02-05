<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Middleware;

use Cake\Event\EventApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class EventMiddleware
{
    /**
     * Application instance that implements `event()` hook.
     *
     * @var \Cake\Event\EventApplicationInterface
     */
    protected $app;

    /**
     * Event dispatcher.
     *
     * @var \Cake\Event\EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @param \Cake\Event\EventApplicationInterface $app Application instance.
     * @param \Cake\Event\EventDispatcherInterface|null $dispatcher Event dispatcher. Can be ommited if `$app` implements `\Cake\Event\EventDispatcherInterface`.
     * @throws RuntimeException
     */
    public function __construct(EventApplicationInterface $app, EventDispatcherInterface $dispatcher = null)
    {
        $this->app = $app;
        if ($dispatcher === null) {
            if ($app instanceof EventDispatcherInterface) {
                throw new RuntimeException('Event dispatcher has not been provided.');
            }
            $dispatcher = $app;
        }
        $this->dispatcher = $dispatcher;
    }

    /**
     * Middleware invoke method.
     *
     * Executes `events()` callback on a `$dispatcher`'s event manager.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request.
     * @param \Psr\Http\Message\ResponseInterface $response Response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $eventManager = $this->dispatcher->getEventManager();
        $eventManager = $this->app->events($eventManager);
        $this->dispatcher->setEventManager($eventManager);

        return $next($request, $response);
    }
}
