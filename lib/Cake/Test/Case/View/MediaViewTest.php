<?php
/**
 * MediaViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('MediaView', 'View');
App::uses('CakeResponse', 'Network');

/**
 * MediaViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class MediaViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->MediaView = new MediaView();
		$this->MediaView->response = $this->getMock('CakeResponse', array(
			'cache',
			'type',
			'disableCache',
			'file',
			'send',
			'compress',
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->MediaView);
	}

/**
 * testRender method
 *
 * @return void
 */
	public function testRender() {
		$vars = array(
			'path' => CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'css' . DS,
			'id' => 'test_asset.css'
		);
		$this->MediaView->viewVars = $vars;

		$this->MediaView->response->expects($this->once())
			->method('disableCache');

		$this->MediaView->response->expects($this->once())
			->method('file')
			->with(
				$vars['path'] . $vars['id'],
				array('name' => null, 'download' => null)
			);

		$this->MediaView->render();
	}

/**
 * Test render() when caching is on.
 *
 * @return void
 */
	public function testRenderCachingAndName() {
		$vars = array(
			'path' => CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'css' . DS,
			'id' => 'test_asset.css',
			'cache' => '+1 day',
			'name' => 'something_special',
			'download' => true,
		);
		$this->MediaView->viewVars = $vars;

		$this->MediaView->response->expects($this->never())
			->method('disableCache');

		$this->MediaView->response->expects($this->once())
			->method('cache')
			->with($this->anything(), $vars['cache']);

		$this->MediaView->response->expects($this->once())
			->method('file')
			->with(
				$vars['path'] . $vars['id'],
				array(
					'name' => 'something_special.css',
					'download' => true
				)
			);

		$this->MediaView->render();
	}

/**
 * Test downloading files with UPPERCASE extensions.
 *
 * @return void
 */
	public function testRenderUpperExtension() {
		$this->MediaView->viewVars = array(
			'path' => CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'img' . DS,
			'id' => 'test_2.JPG'
		);

		$this->MediaView->response->expects($this->any())
			->method('type')
			->with('jpg')
			->will($this->returnArgument(0));

		$this->MediaView->render();
	}

}
