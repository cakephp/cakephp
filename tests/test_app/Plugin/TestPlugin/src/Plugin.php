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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin;

use Cake\Core\BasePlugin;
use Cake\Event\EventManagerInterface;
use Cake\Http\MiddlewareQueue;

class Plugin extends BasePlugin
{
    public function events(EventManagerInterface $events): EventManagerInterface
    {
        $events->on('TestPlugin.load', function (): void {
        });

        return $events;
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue->add(function ($request, $handler) {
            return $handler->handle($request);
        });

        return $middlewareQueue;
    }
}
