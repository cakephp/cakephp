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
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeRoute', 'Routing/Route');

/**
 * Plugin short route, that copies the plugin param to the controller parameters
 * It is used for supporting /:plugin routes.
 *
 * @package Cake.Routing.Route
 */
class PluginShortRoute extends CakeRoute {

/**
 * Parses a string url into an array. If a plugin key is found, it will be copied to the
 * controller parameter
 *
 * @param string $url The url to parse
 * @return mixed false on failure, or an array of request parameters
 */
	public function parse($url) {
		$params = parent::parse($url);
		if (!$params) {
			return false;
		}
		$params['controller'] = $params['plugin'];
		return $params;
	}

/**
 * Reverse route plugin shortcut urls. If the plugin and controller
 * are not the same the match is an auto fail.
 *
 * @param array $url Array of parameters to convert to a string.
 * @return mixed either false or a string url.
 */
	public function match($url) {
		if (isset($url['controller']) && isset($url['plugin']) && $url['plugin'] != $url['controller']) {
			return false;
		}
		$this->defaults['controller'] = $url['controller'];
		$result = parent::match($url);
		unset($this->defaults['controller']);
		return $result;
	}

}
