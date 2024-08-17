<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Console\CommandCollection;
use Cake\Core\PluginInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

trait BasePluginApplicationTrait
{
    public function dispatchEvent(string $name, array $data = [], ?object $subject = null): EventInterface
    {
        return new Event('stub');
    }

    public function setEventManager(EventManagerInterface $eventManager)
    {
        return $this;
    }

    public function getEventManager(): EventManagerInterface
    {
        return new EventManager();
    }

    public function addPlugin(PluginInterface|string $name, array $config = [])
    {
        return $this;
    }

    public function pluginBootstrap(): void
    {
    }

    public function pluginRoutes(RouteBuilder $routes): RouteBuilder
    {
        return $routes;
    }

    public function pluginMiddleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        return $middleware;
    }

    public function pluginConsole(CommandCollection $commands): CommandCollection
    {
        return $commands;
    }
}
