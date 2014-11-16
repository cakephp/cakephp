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
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggingStatement;
use Cake\TestSuite\TestCase;
use PDOStatement;

/**
 * Tests LoggingStatement class
 *
 */
class LoggingStatementTest extends TestCase {

/**
 * Tests that queries are logged when executed without params
 *
 * @return void
 */
	public function testExecuteNoParams() {
		$inner = $this->getMock('PDOStatement');
		$inner->expects($this->once())->method('rowCount')->will($this->returnValue(3));
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
		$logger->expects($this->once())
			->method('log')
			->with($this->logicalAnd(
				$this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
				$this->attributeEqualTo('query', 'SELECT bar FROM foo'),
				$this->attributeEqualTo('took', 5, 200),
				$this->attributeEqualTo('numRows', 3),
				$this->attributeEqualTo('params', [])
			));
		$st = new LoggingStatement($inner);
		$st->queryString = 'SELECT bar FROM foo';
		$st->logger($logger);
		$st->execute();
	}

/**
 * Tests that queries are logged when executed with params
 *
 * @return void
 */
	public function testExecuteWithParams() {
		$inner = $this->getMock('PDOStatement');
		$inner->expects($this->once())->method('rowCount')->will($this->returnValue(4));
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
		$logger->expects($this->once())
			->method('log')
			->with($this->logicalAnd(
				$this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
				$this->attributeEqualTo('query', 'SELECT bar FROM foo'),
				$this->attributeEqualTo('took', 5, 200),
				$this->attributeEqualTo('numRows', 4),
				$this->attributeEqualTo('params', ['a' => 1, 'b' => 2])
			));
		$st = new LoggingStatement($inner);
		$st->queryString = 'SELECT bar FROM foo';
		$st->logger($logger);
		$st->execute(['a' => 1, 'b' => 2]);
	}

/**
 * Tests that queries are logged when executed with bound params
 *
 * @return void
 */
	public function testExecuteWithBinding() {
		$inner = $this->getMock('PDOStatement');
		$inner->expects($this->any())->method('rowCount')->will($this->returnValue(4));
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
		$logger->expects($this->at(0))
			->method('log')
			->with($this->logicalAnd(
				$this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
				$this->attributeEqualTo('query', 'SELECT bar FROM foo'),
				$this->attributeEqualTo('took', 5, 200),
				$this->attributeEqualTo('numRows', 4),
				$this->attributeEqualTo('params', ['a' => 1, 'b' => '2013-01-01'])
			));
		$logger->expects($this->at(1))
			->method('log')
			->with($this->logicalAnd(
				$this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
				$this->attributeEqualTo('query', 'SELECT bar FROM foo'),
				$this->attributeEqualTo('took', 5, 200),
				$this->attributeEqualTo('numRows', 4),
				$this->attributeEqualTo('params', ['a' => 1, 'b' => '2014-01-01'])
			));
		$date = new \DateTime('2013-01-01');
		$inner->expects($this->at(0))->method('bindValue')->with('a', 1);
		$inner->expects($this->at(1))->method('bindValue')->with('b', $date);
		$driver = $this->getMock('\Cake\Database\Driver');
		$st = new LoggingStatement($inner, $driver);
		$st->queryString = 'SELECT bar FROM foo';
		$st->logger($logger);
		$st->bindValue('a', 1);
		$st->bindValue('b', $date, 'date');
		$st->execute();
		$st->bindValue('b', new \DateTime('2014-01-01'), 'date');
		$st->execute();
	}

/**
 * Tests that queries are logged despite database errors
 *
 * @expectedException \LogicException
 * @expectedExceptionMessage This is bad
 * @return void
 */
	public function testExecuteWithError() {
		$exception = new \LogicException('This is bad');
		$inner = $this->getMock('PDOStatement');
		$inner->expects($this->once())->method('execute')
			->will($this->throwException($exception));
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
		$logger->expects($this->once())
			->method('log')
			->with($this->logicalAnd(
				$this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
				$this->attributeEqualTo('query', 'SELECT bar FROM foo'),
				$this->attributeEqualTo('took', 5, 200),
				$this->attributeEqualTo('params', []),
				$this->attributeEqualTo('error', $exception)
			));
		$st = new LoggingStatement($inner);
		$st->queryString = 'SELECT bar FROM foo';
		$st->logger($logger);
		$st->execute();
	}
}
