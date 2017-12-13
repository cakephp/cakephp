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
     * Load all the application configuration and bootstrap logic.
     *
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Disables route loading for the plugin
     *
     * @param bool $disabled True to disable it, false to enable it
     * @return $this
     */
    public function disableRoutes($disabled);

    /**
     * Disables bootstrapping for the plugin
     *
     * @param bool $disabled True to disable it, false to enable it
     * @return $this
     */
    public function disableBootstrap($disabled);

    /**
     * If the routes should be loaded or not for this plugin
     *
     * @return bool
     */
    public function isRouteLoadingEnabled();

    /**
     * If bootstrapping should be done or not for this plugin
     *
     * @return bool
     */
    public function isBootstrapEnabled();
}
