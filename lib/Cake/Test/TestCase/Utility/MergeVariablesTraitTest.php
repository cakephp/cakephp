<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\MergeVariablesTrait;

class Base {
	use MergeVariablesTrait;

	public $listProperty = ['One'];

	public $assocProperty = ['Red'];

	public function mergeVars($properties) {
		return $this->_mergeVars($properties);
	}

}

class Child extends Base {

	public $listProperty = ['Two', 'Three'];

	public $assocProperty = [
		'Green' => ['lime'],
		'Orange'
	];

}

class Grandchild extends Child {

	public $listProperty = ['Four', 'Five'];

	public $assocProperty = [
		'Green' => ['apple'],
		'Yellow' => ['banana']
	];
}

/**
 * MergeVariablesTrait test case
 *
 * @package Cake.Test.TestCase.Utility
 */
class MergeVariablesTraitTest extends TestCase {

/**
 * Test merging vars as a list.
 *
 * @return void
 */
	public function testMergeVarsAsList() {
		$object = new Grandchild();
		$object->mergeVars(['listProperty' => false]);

		$expected = ['One', 'Two', 'Three', 'Four', 'Five'];
		$this->assertSame($expected, $object->listProperty);
	}

/**
 * Test merging vars as an assoc list.
 *
 * @return void
 */
	public function testMergeVarsAsAssoc() {
		$object = new Grandchild();
		$object->mergeVars(['assocProperty' => true]);
		$expected = [
			'Red' => null,
			'Orange' => null,
			'Green' => ['lime', 'apple'],
			'Yellow' => ['banana'],
		];
		$this->assertEquals($expected, $object->assocProperty);
	}
}
