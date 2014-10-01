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
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\Database\Type\FloatType;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Test for the Float type.
 */
class FloatTypeTest extends TestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->type = Type::build('float');
		$this->driver = $this->getMock('Cake\Database\Driver');
	}

/**
 * Test toPHP
 *
 * @return void
 */
	public function testToPHP() {
		$this->assertNull($this->type->toPHP(null, $this->driver));

		$result = $this->type->toPHP('some data', $this->driver);
		$this->assertSame(0.0, $result);

		$result = $this->type->toPHP('2', $this->driver);
		$this->assertSame(2.0, $result);

		$result = $this->type->toPHP('2 bears', $this->driver);
		$this->assertSame(2.0, $result);
	}

/**
 * Test converting to database format
 *
 * @return void
 */
	public function testToDatabase() {
		$result = $this->type->toDatabase('some data', $this->driver);
		$this->assertSame(0.0, $result);

		$result = $this->type->toDatabase(2, $this->driver);
		$this->assertSame(2.0, $result);

		$result = $this->type->toDatabase('2.51', $this->driver);
		$this->assertSame(2.51, $result);
	}

/**
 * Test marshalling
 *
 * @return void
 */
	public function testMarshal() {
		$result = $this->type->marshal('some data', $this->driver);
		$this->assertSame(0.0, $result);

		$result = $this->type->marshal('', $this->driver);
		$this->assertNull($result);

		$result = $this->type->marshal('2.51', $this->driver);
		$this->assertSame(2.51, $result);
	}

/**
 * Test that the PDO binding type is correct.
 *
 * @return void
 */
	public function testToStatement() {
		$this->assertEquals(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
	}

}
