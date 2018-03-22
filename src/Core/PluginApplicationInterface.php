<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventManagerInterface;

/**
 * Interface for Applications that leverage plugins & events.
 *
 * Events can be bound to the application event manager during
 * the application's bootstrap and plugin bootstrap.
 */
interface PluginApplicationInterface extends EventDispatcherInterface
{
    /**
     * Add a plugin to the loaded plugin set.
     *
     * @param string|\Cake\Core\PluginInterface $name The plugin name or plugin object.
     * @param array $config The configuration data for the plugin if using a string for $name
     * @return $this
     */
    public function addPlugin($name, array $config = []);

    /**
     * Run bootstrap logic for loaded plugins.
     *
     * @return void
     */
    public function pluginBootstrap();

    /**
     * Run routes hooks for loaded plugins
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to use.
     * @return \Cake\Routing\RouteBuilder
     */
    public function pluginRoutes($routes);

    /**
     * Run middleware hooks for plugins
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The MiddlewareQueue to use.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function pluginMiddleware($middleware);

    /**
     * Run console hooks for plugins
     *
     * @param \Cake\Console\CommandCollection $commands The CommandCollection to use.
     * @return \Cake\Console\CommandCollection
     */
    public function pluginConsole($commands);
}
