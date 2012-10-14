<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database;

use Cake\Core\Configure;
use Cake\Model\Datasource\Database\Connection;
use Cake\Model\Datasource\Database\Query;

/**
 * Tests Connection class
 *
 **/
class ConnectionTest extends \Cake\TestSuite\TestCase {


	public function setUp() {
		$this->connection = new Connection(Configure::read('Datasource.test'));
	}

	public function tearDown() {
		$this->connection->execute('DROP TABLE IF EXISTS articles');
		$this->connection->execute('DROP TABLE IF EXISTS authors');
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table
 *
 * @return void
 **/
	protected function _insertTwoRecords() {
		$table = 'CREATE TEMPORARY TABLE authors(id int, name varchar(50))';
		$this->connection->execute($table);
		$data = ['id' => '1', 'name' =>  'Chuck Norris'];
		$result = $this->connection->insert('authors', $data, ['id' => 'integer', 'name' => 'string']);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'Bruce Lee');
		$result->execute();

		$table = 'CREATE TEMPORARY TABLE articles(id int, title varchar(20), body varchar(50), author_id int)';
		$this->connection->execute($table);
		$data = ['id' => '1', 'title' =>  'a title', 'body' =>  'a body', 'author_id' => 1];
		$result = $this->connection->insert(
			'articles',
			$data,
			['id' => 'integer', 'title' => 'string', 'body' => 'string', 'author_id' => 'integer']
		);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'another title');
		$result->bindValue(3, 'another body');
		$result->bindValue(4, 2);
		$result->execute();
	}

/**
 * Tests that it is possible to obtain expression results from a query
 *
 * @return void
 **/
	public function testSelectFieldsOnly() {
		$query = new Query($this->connection);
		$result = $query->select('1 + 1')->execute();
		$this->assertInstanceOf('Cake\Model\Datasource\Database\Statement', $result);
		$this->assertEquals([2], $result->fetch());

		//This new field should be appended
		$result = $query->select(array('1 + 3'))->execute();
		$this->assertInstanceOf('Cake\Model\Datasource\Database\Statement', $result);
		$this->assertEquals([2, 4], $result->fetch());

		//This should now overwrite all previous fields
		$result = $query->select(array('1 + 2', '1 + 5'), true)->execute();
		$this->assertEquals([3, 6], $result->fetch());
	}

/**
 * Tests it is possible to select fields from tables with no conditions
 *
 * @return void
 **/
	public function testSelectFieldsFromTable() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query->select(array('body', 'author_id'))->from('articles')->execute();
		$this->assertEquals(array('body' => 'a body', 'author_id' => 1), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'another body', 'author_id' => 2), $result->fetch('assoc'));

		//Append more tables to next execution
		$result = $query->select('name')->from(array('authors'))->execute();
		$this->assertEquals(array('body' => 'a body', 'author_id' => 1, 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'another body', 'author_id' => 2, 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'a body', 'author_id' => 1, 'name' => 'Bruce Lee'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'another body', 'author_id' => 2, 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		//Overwrite tables and only fetch from authors
		$result = $query->select('name', true)->from('authors', true)->execute();
		$this->assertEquals(array('Chuck Norris'), $result->fetch());
		$this->assertEquals(array('Bruce Lee'), $result->fetch());
		$this->assertCount(2, $result);
	}

/**
 * Tests it is possible to add joins to a select query
 *
 * @return void
 **/
	public function testSelectWithJoins() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->execute();

		$this->assertCount(2, $result);
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		$result = $query->join('authors', true)->execute();
		$this->assertCount(4, $result);

		$result = $query->join([['table' => 'authors', 'type' => 'INNER', 'conditions' => 'author_id = 1']], true)->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'a title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));
	}

}
