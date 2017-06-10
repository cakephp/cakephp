<?php
/**
 * TestsAppsPostsController file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestsAppsPostsController
 *
 * @package       Cake.Test.TestApp.Controller
 */
class TestsAppsPostsController extends AppController {

	public $uses = array('Post');

	public $viewPath = 'TestsApps';

/**
 * add method
 *
 * @return void
 */
	public function add() {
		$data = array(
			'Post' => array(
				'title' => 'Test article',
				'body' => 'Body of article.',
				'author_id' => 1
			)
		);
		$this->Post->save($data);

		$this->set('posts', $this->Post->find('all'));
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
		$this->set('posts', $this->Post->find('all'));
		$this->render('index');
	}

}
