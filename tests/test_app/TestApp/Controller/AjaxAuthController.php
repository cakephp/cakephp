<?php
/**
 * AjaxAuthController
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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * AjaxAuthController class
 *
 */
class AjaxAuthController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'TestAuth');

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * testUrl property
 *
 * @var mixed null
 */
	public $testUrl = null;

/**
 * beforeFilter method
 *
 * @param Event $event
 * @return void
 */
	public function beforeFilter(Event $event) {
		$this->TestAuth->ajaxLogin = 'test_element';
		$this->TestAuth->userModel = 'AuthUser';
		$this->TestAuth->RequestHandler->ajaxLayout = 'ajax2';
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->TestAuth->testStop !== true) {
			echo 'Added Record';
		}
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
}
