<?php
/**
 * PHP Version 5.4
 *
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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\ORM\Table;

/**
 * Tests Table class
 *
 */
class TableTest extends \Cake\TestSuite\TestCase {

	public function setUp() {
		$this->connection = new Connection(Configure::read('Datasource.test'));
	}

	public function tearDown() {
		$this->connection->execute('DROP TABLE IF EXISTS things');
		$this->connection->execute('DROP TABLE IF EXISTS dates');
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table
 *
 * @return void
 **/
	protected function _createThingsTable() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = ['id' => '1', 'title' => 'a title', 'body' => 'a body'];
		$result = $this->connection->insert(
			'things',
			$data,
			['id' => 'integer', 'title' => 'string', 'body' => 'string']
		);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'another title');
		$result->bindValue(3, 'another body');
		$result->execute();
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table containing dates
 *
 * @return void
 **/
	protected function _createDatesTable() {
		$table = 'CREATE TEMPORARY TABLE dates(id int, name varchar(50), posted timestamp, visible char(1))';
		$this->connection->execute($table);
		$data = [
			'id' => '1',
			'name' => 'Chuck Norris',
			'posted' => new \DateTime('2012-12-21 12:00'),
			'visible' => 'Y'
		];
		$result = $this->connection->insert(
			'dates',
			$data,
			['id' => 'integer', 'name' => 'string', 'posted' => 'datetime', 'visible' => 'string']
		);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'Bruce Lee');
		$result->bindValue(3, new \DateTime('2012-12-22 12:00'), 'datetime');
		$result->bindValue(4, 'N');
		$result->execute();

		$result->bindValue(1, 3, 'integer');
		$result->bindValue(2, 'Jet Li');
		$result->bindValue(3, new \DateTime('2012-12-25 12:00'), 'datetime');
		$result->bindValue(4, null);
		$result->execute();

		return $result;
	}

	public function testFindAllNoFields() {
		$this->_createThingsTable();
		$table = new Table(['name' => 'things', 'connection' => $this->connection]);
		$results = $table->find('all')->toArray();
		$expected = [
			['things' => ['id' => 1, 'title' => 'a title', 'body' => 'a body']],
			['things' => ['id' => 2, 'title' => 'another title', 'body' => 'another body']]
		];
		$this->assertSame($expected, $results);
	}

	public function testFindAllSomeFields() {
		$this->_createThingsTable();
		$table = new Table(['name' => 'things', 'connection' => $this->connection]);
		$results = $table->find('all')->select(['id', 'title'])->toArray();
		$expected = [
			['things' => ['id' => 1, 'title' => 'a title']],
			['things' => ['id' => 2, 'title' => 'another title']]
		];
		$this->assertSame($expected, $results);

		$results = $table->find('all')->select(['id', 'foo' => 'title'])->toArray();
		$expected = [
			['things' => ['id' => 1, 'foo' => 'a title']],
			['things' => ['id' => 2, 'foo' => 'another title']]
		];
		$this->assertSame($expected, $results);
	}

	public function testFindAllConditionAutoTypes() {
		$this->_createDatesTable();
		$table = new Table(['name' => 'dates', 'connection' => $this->connection]);
		$query = $table->find('all')
			->select(['id', 'name'])
			->where(['posted >=' => new \DateTime('2012-12-22 12:01')]);
		$expected = [
			['dates' => ['id' => 3, 'name' => 'Jet Li']]
		];
		$this->assertSame($expected, $query->toArray());

		$query->orWhere(['dates.posted' => new \DateTime('2012-12-22 12:00')]);
		$expected = [
			['dates' => ['id' => 2, 'name' => 'Bruce Lee']],
			['dates' => ['id' => 3, 'name' => 'Jet Li']]
		];
		$this->assertSame($expected, $query->toArray());
	}
}
