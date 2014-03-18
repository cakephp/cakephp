<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

/**
 * Connects the default, built-in routes, including prefix and plugin routes. The following routes are created
 * in the order below:
 *
 * For each of the Routing.prefixes the following routes are created. Routes containing `:plugin` are only
 * created when your application has one or more plugins.
 *
 * - `/:prefix/:plugin` a plugin shortcut route.
 * - `/:prefix/:plugin/:controller`
 * - `/:prefix/:plugin/:controller/:action/*`
 * - `/:prefix/:controller`
 * - `/:prefix/:controller/:action/*`
 *
 * If plugins are found in your application the following routes are created:
 *
 * - `/:plugin` a plugin shortcut route.
 * - `/:plugin/:controller`
 * - `/:plugin/:controller/:action/*`
 *
 * And lastly the following catch-all routes are connected.
 *
 * - `/:controller'
 * - `/:controller/:action/*'
 *
 * You can disable the connection of default routes by deleting the require inside APP/Config/routes.php.
 */

$prefixes = Router::prefixes();
$prefixPattern = implode('|', $prefixes);
$plugins = Plugin::loaded();
foreach ($plugins as $key => $value) {
	$plugins[$key] = Inflector::underscore($value);
}
$pluginPattern = implode('|', $plugins);
$indexParams = ['action' => 'index'];
$pluginShortMatch = [
	'routeClass' => 'Cake\Routing\Route\PluginShortRoute',
	'_name' => '_plugin._controller:index'
];

if ($prefixPattern && $pluginPattern) {
	$match = ['prefix' => $prefixPattern, 'plugin' => $pluginPattern];
	Router::connect('/:prefix/:plugin', $indexParams, $match + $pluginShortMatch);
	Router::connect('/:prefix/:plugin/:controller', $indexParams, $match);
	Router::connect('/:prefix/:plugin/:controller/:action/*', [], $match);
}
if ($pluginPattern) {
	$match = ['plugin' => $pluginPattern];
	Router::connect('/:plugin', $indexParams, $match + $pluginShortMatch);
	Router::connect('/:plugin/:controller', $indexParams, $match);
	Router::connect('/:plugin/:controller/:action/*', [], $match);
}
if ($prefixPattern) {
	$match = ['prefix' => $prefixPattern];
	Router::connect('/:prefix/:controller', $indexParams, $match);
	Router::connect('/:prefix/:controller/:action/*', [], $match);
}
Router::connect('/:controller', ['action' => 'index']);
Router::connect('/:controller/:action/*');

unset($prefixes, $prefixPattern, $plugins, $pluginPattern, $indexParams, $match);
