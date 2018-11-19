<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

/**
 * Plugin Interface
 */
interface PluginInterface
{
    /**
     * List of valid hooks.
     */
    const VALID_HOOKS = ['routes', 'bootstrap', 'console', 'middleware'];

    /**
     * Get the name of this plugin.
     *
     * @return string
     */
    public function getName();

    /**
     * Get the filesystem path to this plugin
     *
     * @return string
     */
    public function getPath();

    /**
     * Get the filesystem path to configuration for this plugin
     *
     * @return string
     */
    public function getConfigPath();

    /**
     * Get the filesystem path to configuration for this plugin
     *
     * @return string
     */
    public function getClassPath();

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * The default implementation of this method will include the `config/bootstrap.php` in the plugin if it exist. You
     * can override this method to replace that behavior.
     *
     * The host application is provided as an argument. This allows you to load additional
     * plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app);

    /**
     * Add console commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update
     * @return \Cake\Console\CommandCollection
     */
    public function console($commands);

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middleware);

    /**
     * Add routes for the plugin.
     *
     * The default implementation of this method will include the `config/routes.php` in the plugin if it exists. You
     * can override this method to replace that behavior.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes($routes);

    /**
     * Disables the named hook
     *
     * @param string $hook The hook to disable
     * @return $this
     */
    public function disable($hook);

    /**
     * Enables the named hook
     *
     * @param string $hook The hook to disable
     * @return $this
     */
    public function enable($hook);

    /**
     * Check if the named hook is enabled
     *
     * @param string $hook The hook to check
     * @return bool
     */
    public function isEnabled($hook);
}
