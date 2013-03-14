<?php
/**
 * RequestActionController file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller;

/**
 * RequestActionController class
 *
 * @package Cake.Test.TestApp.Controller
 */
class RequestActionController extends AppController {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = ['Post'];

/**
 * test_request_action method
 *
 * @access public
 * @return void
 */
	public function test_request_action() {
		return 'This is a test';
	}

/**
 * another_ra_test method
 *
 * @param mixed $id
 * @param mixed $other
 * @access public
 * @return void
 */
	public function another_ra_test($id, $other) {
		return $id + $other;
	}

/**
 * normal_request_action method
 *
 * @return void
 */
	public function normal_request_action() {
		return 'Hello World';
	}

/**
 * returns $this->here
 *
 * @return void
 */
	public function return_here() {
		return $this->here;
	}

/**
 * paginate_request_action method
 *
 * @return void
 */
	public function paginate_request_action() {
		$data = $this->paginate();
		return true;
	}

/**
 * post pass, testing post passing
 *
 * @return array
 */
	public function post_pass() {
		return $this->request->data;
	}

/**
 * query pass, testing query passing
 *
 * @return array
 */
	public function query_pass() {
		return $this->request->query;
	}

/**
 * test param passing and parsing.
 *
 * @return array
 */
	public function params_pass() {
		return $this->request;
	}

	public function param_check() {
		$this->autoRender = false;
		$content = '';
		if (isset($this->request->params[0])) {
			$content = 'return found';
		}
		$this->response->body($content);
	}
}
