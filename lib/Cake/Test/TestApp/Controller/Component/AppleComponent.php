<?php
/**
 * AppleComponent
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;

/**
 * AppleComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class AppleComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Orange');

/**
 * testName property
 *
 * @var mixed null
 */
	public $testName = null;

/**
 * startup method
 *
 * @param mixed $controller
 * @return void
 */
	public function startup(Controller $controller) {
		$this->testName = $controller->name;
	}
}
