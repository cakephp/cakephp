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
namespace Cake\Test\TestCase\Database\Statement;

use Cake\Database\Statement\StatementDecorator;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Tests StatementDecorator class
 *
 */
class StatemetDecoratorTest extends TestCase {

/**
 * Tests that calling lastInsertId will proxy it to
 * the driver's lastInsertId method
 *
 * @return void
 */
	public function testLastInsertId() {
		$statement = $this->getMock('\PDOStatement');
		$driver = $this->getMock('\Cake\Database\Driver');
		$statement = new StatementDecorator($statement, $driver);

		$driver->expects($this->once())->method('lastInsertId')
			->with('users')
			->will($this->returnValue(2));
		$this->assertEquals(2, $statement->lastInsertId('users'));
	}

/**
 * Tests that calling lastInsertId will get the
 *
 * @return void
 */
	public function testLastInsertIdWithReturning() {
		$internal = $this->getMock('\PDOStatement');
		$driver = $this->getMock('\Cake\Database\Driver');
		$statement = new StatementDecorator($internal, $driver);

		$internal->expects($this->once())->method('columnCount')
			->will($this->returnValue(1));
		$internal->expects($this->once())->method('fetch')
			->with('assoc')
			->will($this->returnValue(['id' => 2]));
		$driver->expects($this->never())->method('lastInsertId');
		$this->assertEquals(2, $statement->lastInsertId('users', 'id'));
	}

}
