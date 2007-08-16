<?php
/* SVN FILE: $Id$ */
/**
 * Basic Cake functionality.
 *
 * Core functions for including other source files, loading models and so forth.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!defined('PHP5')) {
	define ('PHP5', (phpversion() >= 5));
}
/**
 * Configuration, directory layout and standard libraries
 */
	if (!isset($bootstrap)) {
		require CORE_PATH . 'cake' . DS . 'basics.php';
		$TIME_START = getMicrotime();
		require CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php';
		require LIBS . 'object.php';
		require LIBS . 'configure.php';
		require APP_PATH . 'config' . DS . 'core.php';
	}
	require LIBS . 'cache.php';
	require LIBS . 'session.php';
	require LIBS . 'security.php';
	require LIBS . 'inflector.php';

	if (isset($cakeCache)) {
		$cache = 'File';
		$settings = array();

		if (isset($cakeCache[0])) {
			$cache = $cakeCache[0];
		}
		if (isset($cakeCache[1])) {
			$settings = $cakeCache[1];
		}
		Cache::engine($cache, $settings);
	} else {
		Cache::engine();
	}

	Configure::store(null, 'class.paths');
	Configure::load('class.paths');

	if (defined('DEBUG')) {
		Configure::write('debug', DEBUG);
	}
/**
 * Check for IIS Server
 */
	if (!defined('SERVER_IIS') && php_sapi_name() == 'isapi') {
		define('SERVER_IIS', true);
	}

	$url = null;
	require CAKE . 'dispatcher.php';
?>