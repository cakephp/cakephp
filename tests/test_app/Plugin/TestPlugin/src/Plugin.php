<?php
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

class Plugin extends BasePlugin
{
    public function events(EventManagerInterface $events)
    {
        $events->on('TestPlugin.load', function () {
        });

        return $events;
    }

    public function middleware($middleware)
    {
        $middleware->add(function ($req, $res, $next) {
            return $next($req, $res);
        });

        return $middleware;
    }
}
