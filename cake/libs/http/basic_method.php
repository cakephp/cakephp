<?php
/**
 * Basic authentication
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.http
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Basic authentication
 *
 * @package       cake
 * @subpackage    cake.cake.libs.http
 */
class BasicMethod {

/**
 * Authentication
 *
 * @param HttpSocket $http
 * @return void
 * @throws Exception
 */
	public static function authentication(&$http) {
		if (isset($http->request['auth']['user'], $http->request['auth']['pass'])) {
			$http->request['header']['Authorization'] = 'Basic ' . base64_encode($http->request['auth']['user'] . ':' . $http->request['auth']['pass']);
		}
	}

}
