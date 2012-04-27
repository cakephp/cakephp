<?php
/**
 * Controller Merge vars Test file
 *
 * Isolated from the Controller and Component test as to not pollute their AppController class
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Controller', 'Controller');

/**
 * Test case AppController
 *
 * @package       Cake.Test.Case.Controller
 * @package       Cake.Test.Case.Controller
 */
class MergeVarsAppController extends Controller {

/**
 * components
 *
 * @var array
 */
	public $components = array('MergeVar' => array('flag', 'otherFlag', 'redirect' => false));

/**
 * helpers
 *
 * @var array
 */
	public $helpers = array('MergeVar' => array('format' => 'html', 'terse'));
}

/**
 * MergeVar Component
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarComponent extends Object {

}

/**
 * Additional controller for testing
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVariablesController extends MergeVarsAppController {

/**
 * name
 *
 * @var string
 */
	public $name = 'MergeVariables';

/**
 * uses
 *
 * @var arrays
 */
	public $uses = array();

/**
 * parent for mergeVars
 *
 * @var string
 */
	protected $_mergeParent = 'MergeVarsAppController';
}

/**
 * MergeVarPlugin App Controller
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarPluginAppController extends MergeVarsAppController {

/**
 * components
 *
 * @var array
 */
	public $components = array('Auth' => array('setting' => 'val', 'otherVal'));

/**
 * helpers
 *
 * @var array
 */
	public $helpers = array('Javascript');

/**
 * parent for mergeVars
 *
 * @var string
 */
	protected $_mergeParent = 'MergeVarsAppController';
}

/**
 * MergePostsController
 *
 * @package       Cake.Test.Case.Controller
 */
class MergePostsController extends MergeVarPluginAppController {

/**
 * name
 *
 * @var string
 */
	public $name = 'MergePosts';

/**
 * uses
 *
 * @var array
 */
	public $uses = array();
}


/**
 * Test Case for Controller Merging of Vars.
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerMergeVarsTest extends CakeTestCase {

/**
 * test that component settings are not duplicated when merging component settings
 *
 * @return void
 */
	public function testComponentParamMergingNoDuplication() {
		$Controller = new MergeVariablesController();
		$Controller->constructClasses();

		$expected = array('MergeVar' => array('flag', 'otherFlag', 'redirect' => false));
		$this->assertEquals($expected, $Controller->components, 'Duplication of settings occurred. %s');
	}

/**
 * test component merges with redeclared components
 *
 * @return void
 */
	public function testComponentMergingWithRedeclarations() {
		$Controller = new MergeVariablesController();
		$Controller->components['MergeVar'] = array('remote', 'redirect' => true);
		$Controller->constructClasses();

		$expected = array('MergeVar' => array('flag', 'otherFlag', 'redirect' => true, 'remote'));
		$this->assertEquals($expected, $Controller->components, 'Merging of settings is wrong. %s');
	}

/**
 * test merging of helpers array, ensure no duplication occurs
 *
 * @return void
 */
	public function testHelperSettingMergingNoDuplication() {
		$Controller = new MergeVariablesController();
		$Controller->constructClasses();

		$expected = array('MergeVar' => array('format' => 'html', 'terse'));
		$this->assertEquals($expected, $Controller->helpers, 'Duplication of settings occurred. %s');
	}

/**
 * Test that helpers declared in appcontroller come before those in the subclass
 * orderwise
 *
 * @return void
 */
	public function testHelperOrderPrecedence() {
		$Controller = new MergeVariablesController();
		$Controller->helpers = array('Custom', 'Foo' => array('something'));
		$Controller->constructClasses();

		$expected = array(
			'MergeVar' => array('format' => 'html', 'terse'),
			'Custom' => null,
			'Foo' => array('something')
		);
		$this->assertSame($expected, $Controller->helpers, 'Order is incorrect.');
	}

/**
 * test merging of vars with plugin
 *
 * @return void
 */
	public function testMergeVarsWithPlugin() {
		$Controller = new MergePostsController();
		$Controller->components = array('Email' => array('ports' => 'open'));
		$Controller->plugin = 'MergeVarPlugin';
		$Controller->constructClasses();

		$expected = array(
			'MergeVar' => array('flag', 'otherFlag', 'redirect' => false),
			'Auth' => array('setting' => 'val', 'otherVal'),
			'Email' => array('ports' => 'open')
		);
		$this->assertEquals($expected, $Controller->components, 'Components are unexpected.');

		$expected = array(
			'MergeVar' => array('format' => 'html', 'terse'),
			'Javascript' => null
		);
		$this->assertEquals($expected, $Controller->helpers, 'Helpers are unexpected.');

		$Controller = new MergePostsController();
		$Controller->components = array();
		$Controller->plugin = 'MergeVarPlugin';
		$Controller->constructClasses();

		$expected = array(
			'MergeVar' => array('flag', 'otherFlag', 'redirect' => false),
			'Auth' => array('setting' => 'val', 'otherVal'),
		);
		$this->assertEquals($expected, $Controller->components, 'Components are unexpected.');
	}

/**
 * Ensure that _mergeControllerVars is not being greedy and merging with
 * AppController when you make an instance of Controller
 *
 * @return void
 */
	public function testMergeVarsNotGreedy() {
		$Controller = new Controller();
		$Controller->components = array();
		$Controller->uses = array();
		$Controller->constructClasses();

		$this->assertFalse(isset($Controller->Session));
	}
}
