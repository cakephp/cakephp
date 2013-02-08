<?php
/**
 * HTTP Response from HttpSocket.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('HttpSocketResponse', 'Network/Http');

if (class_exists('HttpResponse')) {
	trigger_error(__d(
		'cake_dev',
		"HttpResponse is deprecated due to naming conflicts. Use HttpSocketResponse instead."
	), E_USER_ERROR);
}

/**
 * HTTP Response from HttpSocket.
 *
 * @package       Cake.Network.Http
 * @deprecated This class is deprecated as it has naming conflicts with pecl/http
 */
class HttpResponse extends HttpSocketResponse {

}
