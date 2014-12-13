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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Form;

use Cake\Form\Schema;
use Cake\TestSuite\TestCase;

/**
 * Form schema test case.
 */
class SchemaTest extends TestCase {

/**
 * test adding fields.
 *
 * @return void
 */
	public function testAddingFields() {
		$schema = new Schema();

		$res = $schema->addField('name', ['type' => 'string']);
		$this->assertSame($schema, $res, 'Should be chainable');

		$this->assertEquals(['name'], $schema->fields());
		$res = $schema->field('name');
		$expected = ['type' => 'string', 'length' => null, 'required' => false];
		$this->assertEquals($expected, $res);
	}

/**
 * test adding field whitelist attrs
 *
 * @return void
 */
	public function testAddingFieldsWhitelist() {
		$schema = new Schema();

		$schema->addField('name', ['derp' => 'derp', 'type' => 'string']);
		$expected = ['type' => 'string', 'length' => null, 'required' => false];
		$this->assertEquals($expected, $schema->field('name'));
	}

/**
 * Test removing fields.
 *
 * @return void
 */
	public function testRemovingFields() {
		$schema = new Schema();

		$schema->addField('name', ['type' => 'string']);
		$this->assertEquals(['name'], $schema->fields());

		$res = $schema->removeField('name');
		$this->assertSame($schema, $res, 'Should be chainable');
		$this->assertEquals([], $schema->fields());
		$this->assertNull($schema->field('name'));
	}

}
