<?php
/**
 * RequestHandlerTestController
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

/**
 * RequestHandlerTestController class
 *
 */
class RequestHandlerTestController extends Controller {

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;

/**
 * test method for ajax redirection
 *
 * @return void
 */
	public function destination() {
		$this->viewPath = 'Posts';
		$this->render('index');
	}

/**
 * test method for ajax redirection + parameter parsing
 *
 * @return void
 */
	public function param_method($one = null, $two = null) {
		echo "one: $one two: $two";
		$this->autoRender = false;
	}

/**
 * test method for testing layout rendering when isAjax()
 *
 * @return void
 */
	public function ajax2_layout() {
		if ($this->autoLayout) {
			$this->layout = 'ajax2';
		}
		$this->destination();
	}
}
