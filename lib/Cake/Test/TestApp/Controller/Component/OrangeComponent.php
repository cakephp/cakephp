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
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;

/**
 * OrangeComponent class
 *
 */
class OrangeComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Banana');

/**
 * initialize method
 *
 * @param mixed $controller
 * @return void
 */
	public function initialize(Controller $controller) {
		$this->Controller = $controller;
		$this->Banana->testField = 'OrangeField';
	}

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 */
	public function startup(Controller $controller) {
		$controller->foo = 'pass';
	}
}
