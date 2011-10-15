<?php
/**
 * TestsAppsPostsController file
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
 * @package       Cake.Test.test_app.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestsAppsPostsController extends AppController {
	public $name = 'TestsAppsPosts';
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

/**
 * Fixturized action for testAction()
 *
 */
	public function fixtured() {
		$this->set('posts', $this->Post->find('all'));
		$this->render('index');
	}

}
