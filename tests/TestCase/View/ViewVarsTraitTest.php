<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\ViewVarsTrait;

/**
 * ViewVarsTrait test case
 *
 */
class ViewVarsTraitTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->subject = $this->getObjectForTrait('Cake\View\ViewVarsTrait');
	}

/**
 * Test set() with one param.
 *
 * @return void
 */
	public function testSetOneParam() {
		$data = ['test' => 'val', 'foo' => 'bar'];
		$this->subject->set($data);
		$this->assertEquals($data, $this->subject->viewVars);

		$update = ['test' => 'updated'];
		$this->subject->set($update);
		$this->assertEquals('updated', $this->subject->viewVars['test']);
	}

/**
 * test set() with 2 params
 *
 * @return void
 */
	public function testSetTwoParam() {
		$this->subject->set('testing', 'value');
		$this->assertEquals(['testing' => 'value'], $this->subject->viewVars);
	}

/**
 * test set() with 2 params in combine mode
 *
 * @return void
 */
	public function testSetTwoParamCombind() {
		$keys = ['one', 'key'];
		$vals = ['two', 'val'];
		$this->subject->set($keys, $vals);

		$expected = ['one' => 'two', 'key' => 'val'];
		$this->assertEquals($expected, $this->subject->viewVars);
	}

}
