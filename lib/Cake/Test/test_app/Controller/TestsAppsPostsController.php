<?php
/**
 * TestsAppsPostsController file
 *
 * PHP 5
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
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestsAppsPostsController
 *
 * @package       Cake.Test.TestApp.Controller
 */
class TestsAppsPostsController extends AppController {

	public $uses = array('Post');

	public $viewPath = 'TestsApps';

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
 * check url params
 *
 */
	public function url_var() {
		$this->set('params', $this->request->params);
		$this->render('index');
	}

/**
 * post var testing
 *
 */
	public function post_var() {
		$this->set('data', $this->request->data);
		$this->render('index');
	}

	public function input_data() {
		$this->set('data', $this->request->input('json_decode', true));
		$this->render('index');
	}

/**
 * Fixturized action for testAction()
 *
 */
	public function fixtured() {
		$this->set('posts', $this->Post->find('all'));
		$this->render('index');
	}

}
