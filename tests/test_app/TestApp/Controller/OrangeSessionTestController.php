<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * OrangeSessionTestController class
 *
 */
class OrangeSessionTestController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * session_id method
 *
 * @return void
 */
	public function session_id() {
		$this->response->body($this->Session->id());
	}
}
