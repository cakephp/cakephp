<?php
/**
 * MediaViewTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View;
use Cake\Controller\Controller;
use Cake\TestSuite\TestCase;
use Cake\View\MediaView;

/**
 * MediaViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class MediaViewTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->MediaView = new MediaView();
		$this->MediaView->response = $this->getMock('Cake\Network\Response', array(
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

		$this->MediaView->response->expects($this->once())
			->method('send');

		$result = $this->MediaView->render();
		$this->assertTrue($result);
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

		$this->MediaView->response->expects($this->once())
			->method('send');

		$result = $this->MediaView->render();
		$this->assertTrue($result);
	}

/**
 * Test downloading files with UPPERCASE extensions.
 *
 * @return void
 */
	public function testRenderUpperExtension() {
		return;
		$this->MediaView->viewVars = array(
			'path' => CAKE . 'Test/TestApp/Vendor/img/',
			'id' => 'test_2.JPG'
		);

		$this->MediaView->response->expects($this->any())
			->method('type')
			->with('jpg')
			->will($this->returnArgument(0));

		$this->MediaView->response->expects($this->at(0))
			->method('send')
			->will($this->returnValue(true));

		$this->MediaView->render();
	}

}
