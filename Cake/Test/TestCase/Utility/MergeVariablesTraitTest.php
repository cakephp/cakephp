<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\MergeVariablesTrait;

class Base {

	use MergeVariablesTrait;

	public $hasBoolean = false;

	public $listProperty = ['One'];

	public $assocProperty = ['Red'];

	public function mergeVars($properties, $options = []) {
		return $this->_mergeVars($properties, $options);
	}

}

class Child extends Base {

	public $hasBoolean = ['test'];

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
 */
class MergeVariablesTraitTest extends TestCase {

/**
 * Test merging vars as a list.
 *
 * @return void
 */
	public function testMergeVarsAsList() {
		$object = new Grandchild();
		$object->mergeVars(['listProperty']);

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
		$object->mergeVars(['assocProperty'], ['associative' => ['assocProperty']]);
		$expected = [
			'Red' => null,
			'Orange' => null,
			'Green' => ['lime', 'apple'],
			'Yellow' => ['banana'],
		];
		$this->assertEquals($expected, $object->assocProperty);
	}

/**
 * Test merging vars with mixed modes.
 */
	public function testMergeVarsMixedModes() {
		$object = new Grandchild();
		$object->mergeVars(['assocProperty', 'listProperty'], ['associative' => ['assocProperty']]);
		$expected = [
			'Red' => null,
			'Orange' => null,
			'Green' => ['lime', 'apple'],
			'Yellow' => ['banana'],
		];
		$this->assertEquals($expected, $object->assocProperty);

		$expected = ['One', 'Two', 'Three', 'Four', 'Five'];
		$this->assertSame($expected, $object->listProperty);
	}

/**
 * Test that merging variables with booleans in the class hierarchy
 * doesn't cause issues.
 *
 * @return void
 */
	public function testMergeVarsWithBoolean() {
		$object = new Child();
		$object->mergeVars(['hasBoolean']);
		$this->assertEquals(['test'], $object->hasBoolean);
	}

}
