<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'controller', 'view'.DS.'view');

class PostsController extends Controller {
	var $name = 'Posts';
	function index() {
		$this->set('testData', 'Some test data');
		$test2 = 'more data';
		$test3 = 'even more data';
		$this->set(compact('test2', 'test3'));
	}
}

class TestView extends View {

	function renderElement($name, $params = array()) {
		return $name;
	}
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class ViewTest extends UnitTestCase {

	function setUp() {
		Router::reload();
		$this->PostsController = new PostsController();
		$this->PostsController->index();
		$this->view = new View($this->PostsController);
	}

	function testViewVars() {
		$this->assertEqual($this->view->viewVars, array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'));
	}

	function testUUIDGeneration() {
		$result = $this->view->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form0425fe3bad');
		$result = $this->view->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'forma9918342a7');
		$result = $this->view->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form3ecf2e3e96');
	}

	function testAddInlineScripts() {
		$this->view->addScript('prototype.js');
		$this->view->addScript('prototype.js');
		$this->assertEqual($this->view->__scripts, array('prototype.js'));

		$this->view->addScript('mainEvent', 'Event.observe(window, "load", function() { doSomething(); }, true);');
		$this->assertEqual($this->view->__scripts, array('prototype.js', 'mainEvent' => 'Event.observe(window, "load", function() { doSomething(); }, true);'));
	}

	function testElementCache() {
		$View = new TestView($this->PostsController);
		$element = 'element_name';
		$result = $View->element($element);
		$this->assertEqual($result, $element);

		$cached = false;
		$result = $View->element($element, array('cache'=>'+1 second'));
		if(file_exists(CACHE . 'views' . DS . 'element_cache_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_cache_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>'+1 second', 'other_param'=> true, 'anotherParam'=> true));
		if(file_exists(CACHE . 'views' . DS . 'element_cache_other_param_anotherParam_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_cache_other_param_anotherParam_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>array('time'=>'+1 second', 'key'=>'/whatever/here')));
		if(file_exists(CACHE . 'views' . DS . 'element_'.convertSlash('/whatever/here').'_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_'.convertSlash('/whatever/here').'_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>array('time'=>'+1 second', 'key'=>'whatever_here')));
		if(file_exists(CACHE . 'views' . DS . 'element_whatever_here_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_whatever_here_'.$element);
		}
		$this->assertTrue($cached);
		$this->assertEqual($result, $element);

	}

	function tearDown() {
		unset($this->view);
		unset($this->PostsController);
	}
}
?>