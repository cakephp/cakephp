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
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table
 *
 * @return void
 **/
	protected function _createTables() {
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

	public function testFindAllNoFields() {
		$this->_createTables();
		$table = new Table(['name' => 'things', 'connection' => $this->connection]);
		$results = $table->find('all')->toArray();
		$expected = [
			['things' => ['id' => 1, 'title' => 'a title', 'body' => 'a body']],
			['things' => ['id' => 2, 'title' => 'another title', 'body' => 'another body']]
		];
		$this->assertSame($expected, $results);
	}

	public function testFindAllSomeFields() {
		$this->_createTables();
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

}
