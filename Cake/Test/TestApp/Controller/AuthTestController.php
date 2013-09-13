<?php
/**
 * AuthTestController
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Routing\Router;

/**
 * AuthTestController class
 *
 */
class AuthTestController extends Controller {

/**
 * name property
 *
 * @var string 'AuthTest'
 */
	public $name = 'AuthTest';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array('AuthUser');

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'Auth');

/**
 * testUrl property
 *
 * @var mixed null
 */
	public $testUrl = null;

/**
 * construct method
 *
 * @return void
 */
	public function __construct($request = null, $response = null) {
		$request->addParams(Router::parse('/auth_test'));
		$request->here = '/auth_test';
		$request->webroot = '/';
		Router::setRequestInfo($request);
		parent::__construct($request, $response);
	}

/**
 * login method
 *
 * @return void
 */
	public function login() {
	}

/**
 * admin_login method
 *
 * @return void
 */
	public function admin_login() {
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
	}

/**
 * logout method
 *
 * @return void
 */
	public function logout() {
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		echo "add";
	}

/**
 * add method
 *
 * @return void
 */
	public function camelCase() {
		echo "camelCase";
	}

/**
 * redirect method
 *
 * @param mixed $url
 * @param mixed $status
 * @param mixed $exit
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}

/**
 * isAuthorized method
 *
 * @return void
 */
	public function isAuthorized() {
	}

}
