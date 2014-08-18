<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * @var mixed
 */
	public $testUrl = null;

/**
 * add method
 *
 * @return void
 */
	public function add() {
		echo 'Added Record';
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
