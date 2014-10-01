<?php
/**
 * XmlViewTest file
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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\Utility\Xml;
use Cake\View\XmlView;

/**
 * XmlViewTest
 *
 */
class XmlViewTest extends TestCase {

	public function setUp() {
		parent::setUp();
		Configure::write('debug', false);
	}

/**
 * testRenderWithoutView method
 *
 * @return void
 */
	public function testRenderWithoutView() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('users' => array('user' => array('user1', 'user2')));
		$Controller->set(array('users' => $data, '_serialize' => 'users'));
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render(false);

		$this->assertSame(Xml::build($data)->asXML(), $output);
		$this->assertSame('application/xml', $Response->type());

		$data = array(
			array(
				'User' => array(
					'username' => 'user1'
				)
			),
			array(
				'User' => array(
					'username' => 'user2'
				)
			)
		);
		$Controller->set(array('users' => $data, '_serialize' => 'users'));
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render(false);

		$expected = Xml::build(array('response' => array('users' => $data)))->asXML();
		$this->assertSame($expected, $output);

		$Controller->set('_rootNode', 'custom_name');
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render(false);

		$expected = Xml::build(array('custom_name' => array('users' => $data)))->asXML();
		$this->assertSame($expected, $output);
	}

/**
 * Test that rendering with _serialize does not load helpers
 *
 * @return void
 */
	public function testRenderSerializeNoHelpers() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$Controller->helpers = array('Html');
		$Controller->set(array(
			'_serialize' => 'tags',
			'tags' => array('cakephp', 'framework')
		));
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$View->render();
		$this->assertFalse(isset($View->Html), 'No helper loaded.');
	}

/**
 * Test render with an array in _serialize
 *
 * @return void
 */
	public function testRenderWithoutViewMultiple() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('no', 'user'));
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$this->assertSame('application/xml', $Response->type());
		$output = $View->render(false);
		$expected = array(
			'response' => array('no' => $data['no'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);

		$Controller->set('_rootNode', 'custom_name');
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render(false);
		$expected = array(
			'custom_name' => array('no' => $data['no'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);
	}

/**
 * Test render with an array in _serialize and alias
 *
 * @return void
 */
	public function testRenderWithoutViewMultipleAndAlias() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('original_name' => 'my epic name', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('new_name' => 'original_name', 'user'));
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$this->assertSame('application/xml', $Response->type());
		$output = $View->render(false);
		$expected = array(
			'response' => array('new_name' => $data['original_name'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);

		$Controller->set('_rootNode', 'custom_name');
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render(false);
		$expected = array(
			'custom_name' => array('new_name' => $data['original_name'], 'user' => $data['user'])
		);
		$this->assertSame(Xml::build($expected)->asXML(), $output);
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$Controller->name = 'Posts';
		$Controller->viewPath = 'Posts';

		$data = array(
			array(
				'User' => array(
					'username' => 'user1'
				)
			),
			array(
				'User' => array(
					'username' => 'user2'
				)
			)
		);
		$Controller->set('users', $data);
		$Controller->viewClass = 'Xml';
		$View = $Controller->createView();
		$output = $View->render('index');

		$expected = array(
			'users' => array('user' => array('user1', 'user2'))
		);
		$expected = Xml::build($expected)->asXML();
		$this->assertSame($expected, $output);
		$this->assertSame('application/xml', $Response->type());
		$this->assertInstanceOf('Cake\View\HelperRegistry', $View->helpers());
	}

}
