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
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
class QueryTest extends \Cake\TestSuite\TestCase {


	public function setUp() {
		$this->connection = new Connection(Configure::read('Datasource.test'));
		$this->connection->execute('DROP TABLE IF EXISTS articles');
		$this->connection->execute('DROP TABLE IF EXISTS authors');
		$this->connection->execute('DROP TABLE IF EXISTS dates');
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

		return $result;
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table containing dates
 *
 * @return void
 **/
	protected function _insertDateRecords() {
		$table = 'CREATE TEMPORARY TABLE dates(id int, name varchar(50), posted timestamp, visible char(1))';
		$this->connection->execute($table);
		$data = [
			'id' => '1',
			'name' =>  'Chuck Norris',
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
		$result = $query->select('name')->from(array('authors'))->order(['name' => 'desc', 'articles.id' => 'asc'])->execute();
		$this->assertEquals(array('body' => 'a body', 'author_id' => 1, 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'another body', 'author_id' => 2, 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'a body', 'author_id' => 1, 'name' => 'Bruce Lee'), $result->fetch('assoc'));
		$this->assertEquals(array('body' => 'another body', 'author_id' => 2, 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		//Overwrite tables and only fetch from authors
		$result = $query->select('name', true)->from('authors', true)->order(['name' => 'desc'], true)->execute();
		$this->assertEquals(array('Chuck Norris'), $result->fetch());
		$this->assertEquals(array('Bruce Lee'), $result->fetch());
		$this->assertCount(2, $result);
	}

/**
 * Tests it is possible to select aliased fields
 *
 * @return void
 **/
	public function testSelectAliasedFieldsFromTable() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query->select(['text' => 'body', 'author_id'])->from('articles')->execute();
		$this->assertEquals(array('text' => 'a body', 'author_id' => 1), $result->fetch('assoc'));
		$this->assertEquals(array('text' => 'another body', 'author_id' => 2), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query->select(['text' => 'body', 'author' => 'author_id'])->from('articles')->execute();
		$this->assertEquals(array('text' => 'a body', 'author' => 1), $result->fetch('assoc'));
		$this->assertEquals(array('text' => 'another body', 'author' => 2), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$query->select(['text' => 'body'])->select(['author_id', 'foo' => 'body']);
		$result = $query->from('articles')->execute();
		$this->assertEquals(array('foo' => 'a body', 'text' => 'a body', 'author_id' => 1), $result->fetch('assoc'));
		$this->assertEquals(array('foo' => 'another body', 'text' => 'another body', 'author_id' => 2), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$exp = $query->newExpr()->add('1 + 1');
		$comp = $query->newExpr()->add(['author_id +' => 2]);
		$result = $query->select(['text' => 'body', 'two' => $exp, 'three' => $comp])
			->from('articles')->execute();
		$this->assertEquals(array('text' => 'a body', 'two' => 2, 'three' => 3), $result->fetch('assoc'));
		$this->assertEquals(array('text' => 'another body', 'two' => 2, 'three' => 4), $result->fetch('assoc'));
	}

/**
 * Tests that tables can also be aliased and referenced in the select clause using such alias
 *
 * @return void
 **/
	public function testSelectAliasedTables() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query->select(['text' => 'a.body', 'a.author_id'])
			->from(['a' => 'articles'])->execute();
		$this->assertEquals(['text' => 'a body', 'author_id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['text' => 'another body', 'author_id' => 2], $result->fetch('assoc'));

		$result = $query->select(['name' => 'b.name'])->from(['b' => 'authors'])
			->order(['text' => 'desc', 'name' => 'desc'])
			->execute();
		$this->assertEquals(
			['text' => 'another body', 'author_id' => 2, 'name' => 'Chuck Norris'],
			$result->fetch('assoc')
		);
		$this->assertEquals(
			['text' => 'another body', 'author_id' => 2, 'name' => 'Bruce Lee'],
			$result->fetch('assoc')
		);
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

		$result = $query->join('authors', [], true)->execute();
		$this->assertCount(4, $result);

		$result = $query->join([
			['table' => 'authors', 'type' => 'INNER', 'conditions' => 'author_id = 1']
		], [], true)->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'a title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));
	}

/**
 * Tests it is possible to add joins to a select query using array or expression as conditions
 *
 * @return void
 **/
	public function testSelectWithJoinsConditions() {
		$this->_insertTwoRecords();
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => ['author_id' => 1]])
			->execute();
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'a title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$conditions = $query->newExpr()->add(['author_id' => 2]);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => $conditions])
			->execute();
		$this->assertEquals(array('title' => 'another title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$time = new \DateTime('2012-12-21 12:00');
		$types = ['posted' => 'datetime'];
		$result = $query
			->select(['title', 'name' => 'd.name'])
			->from('articles')
			->join(['table' => 'dates', 'alias' => 'd', 'conditions' => ['posted' => $time]], $types)
			->execute();
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
	}

/**
 * Tests that joins can be aliased using array keys
 *
 * @return void
 **/
	public function testSelectAliasedJoins() {
		$this->_insertTwoRecords();
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['a' => 'authors'])
			->order(['name' => 'desc'])
			->execute();
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$conditions = $query->newExpr()->add(['author_id' => 2]);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['a' => ['table' => 'authors', 'conditions' => $conditions]])
			->execute();
		$this->assertEquals(array('title' => 'another title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Bruce Lee'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$time = new \DateTime('2012-12-21 12:00');
		$types = ['posted' => 'datetime'];
		$result = $query
			->select(['title', 'name' => 'd.name'])
			->from('articles')
			->join(['d' => ['table' => 'dates', 'conditions' => ['posted' => $time]]], $types)
			->execute();
		$this->assertEquals(array('title' => 'a title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
		$this->assertEquals(array('title' => 'another title', 'name' => 'Chuck Norris'), $result->fetch('assoc'));
	}

/**
 * Tests it is possible to filter a query by using simple AND joined conditions
 *
 * @return void
 **/
	public function testSelectSimpleWhere() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id' => 1, 'title' => 'a title'])
			->execute();
		$this->assertCount(1, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id' => 100], ['id' => 'integer'])
			->execute();
		$this->assertCount(0, $result);
	}

/**
 * Tests using where conditions with operators and scalar values works
 *
 * @return void
 **/
	public function testSelectWhereOperators() {
		$this->_insertTwoRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id >' => 1])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'another title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id <' => 2])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id <=' => 2])
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id >=' => 1])
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id <=' => 1])
			->execute();
		$this->assertCount(1, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['id !=' => 2])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['title like' => 'a title'])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['title like' => '%title%'])
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(['title not like' => '%title%'])
			->execute();
		$this->assertCount(0, $result);
	}

/**
 * Tests selecting with conditions and specifying types for those
 *
 * @return void
 **/
	public function testSelectWhereTypes() {
		$this->_insertDateRecords();

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted >' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(
				[
					'posted >' => new \DateTime('2012-12-21 12:00'),
					'posted <' => new \DateTime('2012-12-23 12:00')
				],
				['posted' => 'datetime']
			)
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('id' => 2), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(
				[
					'id' => '3something-crazy',
					'posted <' => new \DateTime('2013-01-01 12:00')
				],
				['posted' => 'datetime', 'id' => 'integer']
			)
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(
				[
					'id' => '1something-crazy',
					'posted <' => new \DateTime('2013-01-01 12:00')
				],
				['posted' => 'datetime', 'id' => 'float']
			)
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
	}

/**
 * Tests that Query::orWhere() can be used to concatenate conditions with OR
 *
 * @return void
 **/
	public function testSelectOrWhere() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->orWhere(['posted' => new \DateTime('2012-12-22 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
	}

/**
 * Tests that Query::andWhere() can be used to concatenate conditions with AND
 *
 * @return void
 **/
	public function testSelectAndWhere() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->andWhere(['id' => 1])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->andWhere(['id' => 2])
			->execute();
		$this->assertCount(0, $result);
	}

/**
 * Tests that combining Query::andWhere() and Query::orWhere() produces
 * correct conditions nesting
 *
 * @return void
 **/
	public function testSelectExpressionNesting() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->orWhere(['id' => 2])
			->andWhere(['posted >=' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->orWhere(['id' => 2])
			->andWhere(['posted >=' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->orWhere(['posted' => new \DateTime('2012-12-25 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(3, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));
	}

/**
 * Tests that Query::orWhere() can be used without calling where() before
 *
 * @return void
 **/
	public function testSelectOrWhereNoPreviousCondition() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->orWhere(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->orWhere(['posted' => new \DateTime('2012-12-22 12:00')], ['posted' => 'datetime'])
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
	}

/**
 * Tests that Query::andWhere() can be used without calling where() before
 *
 * @return void
 **/
	public function testSelectAndWhereNoPreviousCondition() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->andWhere(['posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
			->andWhere(['id' => 1])
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
	}

/**
 * Tests that it is possible to pass a closure to where() to build a set of
 * conditions and return the expression to be used
 *
 * @return void
 **/
	public function testSelectWhereUsingClosure() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->eq('id', 1);
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp
					->eq('id', 1)
					->eq('posted',  new \DateTime('2012-12-21 12:00'), 'datetime');
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp
					->eq('id', 1)
					->eq('posted',  new \DateTime('2021-12-30 15:00'), 'datetime');
			})
			->execute();
		$this->assertCount(0, $result);
	}

/**
 * Tests that it is possible to pass a closure to andWhere() to build a set of
 * conditions and return the expression to be used
 *
 * @return void
 **/
	public function testSelectAndWhereUsingClosure() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['id' => '1'])
			->andWhere(function($exp) {
				return $exp->eq('posted',  new \DateTime('2012-12-21 12:00'), 'datetime');
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['id' => '1'])
			->andWhere(function($exp) {
				return $exp->eq('posted',  new \DateTime('2022-12-21 12:00'), 'datetime');
			})
			->execute();
		$this->assertCount(0, $result);
	}

/**
 * Tests that it is possible to pass a closure to orWhere() to build a set of
 * conditions and return the expression to be used
 *
 * @return void
 **/
	public function testSelectOrWhereUsingClosure() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['id' => '1'])
			->orWhere(function($exp) {
				return $exp->eq('posted',  new \DateTime('2012-12-22 12:00'), 'datetime');
			})
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(['id' => '1'])
			->orWhere(function($exp) {
				return $exp
					->eq('posted',  new \DateTime('2012-12-22 12:00'), 'datetime')
					->eq('id',  3);
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
	}

/**
 * Tests using where conditions with operator methods
 *
 * @return void
 **/
	public function testSelectWhereOperatorMethods() {
		$this->_insertTwoRecords();
		$this->_insertDateRecords();

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->gt('id', 1); })
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'another title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->lt('id', 2); })
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->lte('id', 2); })
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->gte('id', 1); })
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->lte('id', 1); })
			->execute();
		$this->assertCount(1, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->notEq('id', 2); })
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->like('title', 'a title'); })
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(array('title' => 'a title'), $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->like('title', '%title%'); })
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['title'])
			->from('articles')
			->where(function($exp) { return $exp->notLike('title', '%title%'); })
			->execute();
		$this->assertCount(0, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) { return $exp->isNull('visible'); })
			->execute();
		$this->assertCount(1, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) { return $exp->isNotNull('visible'); })
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->in('visible', ['Y', 'N']);
			})
			->execute();
		$this->assertCount(2, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->in(
					'posted',
					[new \DateTime('2012-12-21 12:00'), new \DateTime('2012-12-22 12:00')],
					'datetime'
				);
			})
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->notIn(
					'posted',
					[new \DateTime('2012-12-21 12:00'), new \DateTime('2012-12-22 12:00')],
					'datetime'
				);
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));
	}

/**
 * Tests nesting query expressions both using arrays and closures
 *
 * @return void
 **/
	public function testSelectExpressionComposition() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				$and = $exp->and_(['id' => 2, 'id >' => 1]);
				return $exp->add($and);
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				$and = $exp->and_(['id' => 2, 'id <' => 2]);
				return $exp->add($and);
			})
			->execute();
		$this->assertCount(0, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				$and = $exp->and_(function($and) {
					return $and->eq('id', 1)->gt('id', 0);
				});
				return $exp->add($and);
			})
			->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				$or = $exp->or_(['id' => 1]);
				$and = $exp->and_(['id >' => 2, 'id <' => 4]);
				return $or->add($and);
			})
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				$or = $exp->or_(function($or) {
					return $or->eq('id', 1)->eq('id', 2);
				});
				return $or;
			})
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
	}

/**
 * Tests that conditions can be nested with an unary operator using the array notation
 * and the not() method
 *
 * @return void
 **/
	public function testSelectWhereNot() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->not(
					$exp->and_(['id' => 2, 'posted' => new \DateTime('2012-12-22 12:00')], ['posted' => 'datetime'])
				);
			})
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where(function($exp) {
				return $exp->not(
					$exp->and_(['id' => 2, 'posted' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime'])
				);
			})
			->execute();
		$this->assertCount(3, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->where([
				'not' => ['or' => ['id' => 1, 'id >' => 2], 'id' => 3]
			])
			->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
	}

/**
 * Tests order() method both with simple fields and expressions
 *
 * @return void
 **/
	public function testSelectOrderBy() {
		$statement = $this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query
			->select(['id'])
			->from('dates')
			->order(['id' => 'desc'])
			->execute();
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$result = $query->order(['id' => 'asc'])->execute();
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$result = $query->order(['name' => 'asc'])->execute();
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$result = $query->order(['name' => 'asc'], true)->execute();
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$statement->bindValue(1, 4, 'integer');
		$statement->bindValue(2, 'Chuck Norris');
		$statement->bindValue(3, new \DateTime('2012-12-21 12:00'), 'datetime');
		$statement->bindValue(4, 'N');
		$statement->execute();

		$statement->bindValue(1, 5, 'integer');
		$statement->bindValue(2, 'Chuck Norris');
		$statement->bindValue(3, new \DateTime('2012-12-20 12:00'), 'datetime');
		$statement->bindValue(4, 'N');
		$statement->execute();

		$result = $query->order(['name' => 'asc', 'posted' => 'desc', 'visible' => 'asc'], true)
			->execute();
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 4], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 5], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$expression = $query->newExpr()
			->add(['(id + :offset) % 2 = 0'])
			->bind(':offset', 1, null);
		$result = $query->order([$expression, 'id' => 'desc'], true)->execute();
		$this->assertEquals(['id' => 4], $result->fetch('assoc'));
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 5], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$result = $query->order($expression, true)->order(['id' => 'asc'])->execute();
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 4], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));
		$this->assertEquals(['id' => 5], $result->fetch('assoc'));
	}

/**
 * Tests that group by fields can be passed similar to select fields
 * and that it sends the correct query to the database
 *
 * @return void
 **/
	public function testSelectGroup() {
		$statement = $this->_insertTwoRecords();
		$statement->bindValue(1, 3);
		$statement->bindValue(2, 'another title');
		$statement->bindValue(3, 'another body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->execute();
		$expected = [['total' => 1, 'author_id' => 1], ['total' => '2', 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$result = $query->select(['total' => 'count(title)', 'name'], true)
			->group(['name'], true)
			->order(['total' => 'asc'])
			->execute();
		$expected = [['total' => 1, 'name' => 'Chuck Norris'], ['total' => 2, 'name' => 'Bruce Lee']];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$result = $query->select(['articles.id'])
			->group(['articles.id'])
			->execute();
		$this->assertCount(3, $result);
	}

/**
 * Tests that it is possible to select distinct rows, even filtering by one column
 * this is testing that there is an specific implementation for DISTINCT ON
 *
 * @return void
 **/
	public function testSelectDistinct() {
		$result = $this->_insertTwoRecords();
		$result->bindValue(1, '3', 'integer');
		$result->bindValue(2, 'another title');
		$result->bindValue(3, 'another body');
		$result->bindValue(4, 2);
		$result->execute();

		$query = new Query($this->connection);
		$result = $query
			->select(['author_id'])
			->from(['a' => 'articles'])
			->execute();
		$this->assertCount(3, $result);

		$result = $query->distinct()->execute();
		$this->assertCount(2, $result);

		$result = $query->select(['id'])->distinct(false)->execute();
		$this->assertCount(3, $result);

		$result = $query->select(['id'])->distinct(['author_id'])->execute();
		$this->assertCount(2, $result);
	}

/**
 * Tests that having() behaves pretty much the same as the where() method
 *
 * @return void
 **/
	public function testSelectHaving() {
		$statement = $this->_insertTwoRecords();
		$statement->bindValue(1, 3);
		$statement->bindValue(2, 'another title');
		$statement->bindValue(3, 'another body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
			->execute();
		$expected = [['total' => 1, 'author_id' => 1]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$result = $query->having(['count(author_id)' => 2], ['count(author_id)' => 'integer'], true)
			->execute();
		$expected = [['total' => 2, 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$result = $query->having(function($e) { return $e->add('count(author_id) = 1 + 1'); }, [], true)
			->execute();
		$expected = [['total' => 2, 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests that Query::orHaving() can be used to concatenate conditions with OR
 * in the having clause
 *
 * @return void
 **/
	public function testSelectOrHaving() {
		$statement = $this->_insertTwoRecords();
		$statement->bindValue(1, 3);
		$statement->bindValue(2, 'another title');
		$statement->bindValue(3, 'another body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
			->orHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
			->execute();
		$expected = [['total' => 1, 'author_id' => 1]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
			->orHaving(['count(author_id) <=' => 2], ['count(author_id)' => 'integer'])
			->execute();
		$expected = [['total' => 1, 'author_id' => 1], ['total' => 2, 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
			->orHaving(function($e) { return $e->add('count(author_id) = 1 + 1'); })
			->execute();
		$expected = [['total' => 2, 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests that Query::andHaving() can be used to concatenate conditions with AND
 * in the having clause
 *
 * @return void
 **/
	public function testSelectAndHaving() {
		$statement = $this->_insertTwoRecords();
		$statement->bindValue(1, 3);
		$statement->bindValue(2, 'another title');
		$statement->bindValue(3, 'another body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
			->andHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
			->execute();
		$this->assertCount(0, $result);

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->having(['count(author_id)' => 2], ['count(author_id)' => 'integer'])
			->andHaving(['count(author_id) >' => 1], ['count(author_id)' => 'integer'])
			->execute();
		$expected = [['total' => 2, 'author_id' => 2]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$query = new Query($this->connection);
		$result = $query
			->select(['total' => 'count(author_id)', 'author_id'])
			->from('articles')
			->join(['table' => 'authors', 'alias' => 'a', 'conditions' => 'author_id = a.id'])
			->group('author_id')
			->andHaving(function($e) { return $e->add('count(author_id) = 2 - 1'); })
			->execute();
		$expected = [['total' => 1, 'author_id' => 1]];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests selecting rows using a limit clause
 *
 * @return void
 **/
	public function testSelectLimit() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query->select('id')->from('dates')->limit(1)->execute();
		$this->assertCount(1, $result);

		$result = $query->limit(null)->execute();
		$this->assertCount(3, $result);

		$result = $query->limit(2)->execute();
		$this->assertCount(2, $result);

		$result = $query->limit(3)->execute();
		$this->assertCount(3, $result);
	}

/**
 * Tests selecting rows combining a limit and offset clause
 *
 * @return void
 **/
	public function testSelectOffset() {
		$this->_insertDateRecords();
		$query = new Query($this->connection);
		$result = $query->select('id')->from('dates')
			->limit(1)
			->offset(0)->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));

		$result = $query->offset(1)->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));

		$result = $query->offset(2)->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$query = new Query($this->connection);
		$result = $query->select('id')->from('dates')
			->order(['id' => 'desc'])
			->limit(1)
			->offset(0)->execute();
		$this->assertCount(1, $result);
		$this->assertEquals(['id' => 3], $result->fetch('assoc'));

		$result = $query->limit(2)->offset(1)->execute();
		$this->assertCount(2, $result);
		$this->assertEquals(['id' => 2], $result->fetch('assoc'));
		$this->assertEquals(['id' => 1], $result->fetch('assoc'));
	}

/**
 * Tests that Query objects can be included inside the select clause
 * and be used as a normal field, including binding any passed parameter
 *
 * @return void
 **/
	public function testSubqueryInSelect() {
		$this->_insertDateRecords();
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$subquery = (new Query($this->connection))
			->select('name')
			->from(['b' => 'authors'])
			->where(['b.id = a.id']);
		$result = $query
			->select(['id', 'name' => $subquery])
			->from(['a' => 'dates'])->execute();

		$expected = [
			['id' => 1, 'name' => 'Chuck Norris'],
			['id' => 2, 'name' => 'Bruce Lee'],
			['id' => 3, 'name' => null]
		];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$query = new Query($this->connection);
		$subquery = (new Query($this->connection))
			->select('name')
			->from(['b' => 'authors'])
			->where(['name' => 'Chuck Norris'], ['name' => 'string']);
		$result = $query
			->select(['id', 'name' => $subquery])
			->from(['a' => 'dates'])->execute();

		$expected = [
			['id' => 1, 'name' => 'Chuck Norris'],
			['id' => 2, 'name' => 'Chuck Norris'],
			['id' => 3, 'name' => 'Chuck Norris']
		];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests that Query objects can be included inside the from clause
 * and be used as a normal table, including binding any passed parameter
 *
 * @return void
 **/
	public function testSuqueryInFrom() {
		$this->_insertDateRecords();
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$subquery = (new Query($this->connection))
			->select(['id', 'name'])
			->from('dates')
			->where(['posted >' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime']);
		$result = $query
			->select(['name'])
			->from(['b' => $subquery])
			->where(['id !=' => 3])
			->execute();

		$expected = [
			['name' => 'Bruce Lee'],
		];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests that Query objects can be included inside the where clause
 * and be used as a normal condition, including binding any passed parameter
 *
 * @return void
 **/
	public function testSuqueryInWhere() {
		$this->_insertDateRecords();
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$subquery = (new Query($this->connection))
			->select(['id'])
			->from('authors')
			->where(['id >' => 1]);
		$result = $query
			->select(['name'])
			->from(['dates'])
			->where(['id !=' => $subquery])
			->execute();

		$expected = [
			['name' => 'Chuck Norris'],
			['name' => 'Jet Li']
		];
		$this->assertEquals($expected, $result->fetchAll('assoc'));

		$query = new Query($this->connection);
		$subquery = (new Query($this->connection))
			->select(['id'])
			->from('dates')
			->where(['posted >' => new \DateTime('2012-12-21 12:00')], ['posted' => 'datetime']);
		$result = $query
			->select(['name'])
			->from(['authors'])
			->where(['id not in' => $subquery])
			->execute();

		$expected = [
			['name' => 'Chuck Norris'],
		];
		$this->assertEquals($expected, $result->fetchAll('assoc'));
	}

/**
 * Tests that it is possible to use a subquery in a join clause
 *
 * @return void
 **/
	public function testSubqueyInJoin() {
		$this->_insertTwoRecords();
		$this->_insertDateRecords();
		$subquery = (new Query($this->connection))->select('*')->from('authors');

		$query = new Query($this->connection);
		$result = $query
			->select(['title', 'name'])
			->from('articles')
			->join(['b' => $subquery])
			->execute();
		$this->assertCount(4, $result);

		$subquery->where(['id' => 1]);
		$result = $query->execute();
		$this->assertCount(2, $result);

		$query->join(['b' => ['table' => $subquery, 'conditions' => ['b.id = articles.id']]], [], true);
		$result = $query->execute();
		$this->assertCount(1, $result);
	}

/**
 * Tests that it is possible to one or multiple UNION statements in a query
 *
 * @return void
 **/
	public function testUnion() {
		$this->_insertTwoRecords();
		$this->_insertDateRecords();
		$union = (new Query($this->connection))->select(['id', 'title'])->from(['a' => 'articles']);
		$query = new Query($this->connection);
		$result = $query->select(['id', 'name'])
			->from(['d' => 'dates'])
			->union($union)
			->execute();
		$this->assertCount(5, $result);
		$rows = $result->fetchAll();

		$union->select(['foo' => 'id', 'bar' => 'title']);
		$union = (new Query($this->connection))
			->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
			->from(['b' => 'authors'])
			->where(['id ' => 1])
			->order(['id' => 'desc']);

		$result = $query->select(['foo' => 'id', 'bar' => 'name'])->union($union)->execute();
		$this->assertCount(5, $result);
		$this->assertNotEquals($rows, $result->fetchAll());

		$union = (new Query($this->connection))
			->select(['id', 'title'])
			->from(['c' => 'articles']);
		$result = $query->select(['id', 'name'], true)->union($union, true)->execute();
		$this->assertCount(5, $result);
		$this->assertEquals($rows, $result->fetchAll());
	}

}
