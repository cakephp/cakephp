<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * SomePostsController class
 *
 */
class SomePostsController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * autoRender property
 *
 * @var bool false
 */
	public $autoRender = false;

/**
 * beforeFilter method
 *
 * @param Event $event
 * @return void
 */
	public function beforeFilter(Event $event) {
		if ($this->request->params['action'] == 'index') {
			$this->request->params['action'] = 'view';
		} else {
			$this->request->params['action'] = 'change';
		}
		$this->request->params['pass'] = array('changed');
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		return true;
	}

/**
 * change method
 *
 * @return void
 */
	public function change() {
		return true;
	}

}
