<?php
/**
 * TestsAppsPostsController file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestsAppsPostsController
 *
 */
namespace TestApp\Controller;

class TestsAppsPostsController extends AppController {

	public $viewPath = 'TestsApps';

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$this->loadModel('Posts');
		$entity = $this->Posts->newEntity($this->request->data);
		if ($entity) {
			$this->Posts->save($entity);
		}
		$this->set('posts', $this->Posts->find('all'));
		$this->render('index');
	}

/**
 * check URL params
 *
 * @return void
 */
	public function url_var() {
		$this->set('params', $this->request->params);
		$this->render('index');
	}

/**
 * post var testing
 *
 * @return void
 */
	public function post_var() {
		$this->set('data', $this->request->data);
		$this->render('index');
	}

/**
 * input_data()
 *
 * @return void
 */
	public function input_data() {
		$this->set('data', $this->request->input('json_decode', true));
		$this->render('index');
	}

/**
 * Fixturized action for testAction()
 *
 * @return void
 */
	public function fixtured() {
		$this->loadModel('Posts');
		$this->set('posts', $this->Posts->find('all'));
		$this->render('index');
	}

}
