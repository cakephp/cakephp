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
 * @package       Cake.Config
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

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
 * You can disable the connection of default routes by deleting the require inside CONFIG/routes.php.
 */
$prefixes = Router::prefixes();

if ($plugins = CakePlugin::loaded()) {
	App::uses('PluginShortRoute', 'Routing/Route');
	foreach ($plugins as $key => $value) {
		$plugins[$key] = Inflector::underscore($value);
	}
	$pluginPattern = implode('|', $plugins);
	$match = array('plugin' => $pluginPattern, 'defaultRoute' => true);
	$shortParams = array('routeClass' => 'PluginShortRoute', 'plugin' => $pluginPattern, 'defaultRoute' => true);

	foreach ($prefixes as $prefix) {
		$params = array('prefix' => $prefix, $prefix => true);
		$indexParams = $params + array('action' => 'index');
		Router::connect("/{$prefix}/:plugin", $indexParams, $shortParams);
		Router::connect("/{$prefix}/:plugin/:controller", $indexParams, $match);
		Router::connect("/{$prefix}/:plugin/:controller/:action/*", $params, $match);
	}
	Router::connect('/:plugin', array('action' => 'index'), $shortParams);
	Router::connect('/:plugin/:controller', array('action' => 'index'), $match);
	Router::connect('/:plugin/:controller/:action/*', array(), $match);
}

foreach ($prefixes as $prefix) {
	$params = array('prefix' => $prefix, $prefix => true);
	$indexParams = $params + array('action' => 'index');
	Router::connect("/{$prefix}/:controller", $indexParams, array('defaultRoute' => true));
	Router::connect("/{$prefix}/:controller/:action/*", $params, array('defaultRoute' => true));
}
Router::connect('/:controller', array('action' => 'index'), array('defaultRoute' => true));
Router::connect('/:controller/:action/*', array(), array('defaultRoute' => true));

$namedConfig = Router::namedConfig();
if ($namedConfig['rules'] === false) {
	Router::connectNamed(true);
}

unset($namedConfig, $params, $indexParams, $prefix, $prefixes, $shortParams, $match,
	$pluginPattern, $plugins, $key, $value);

