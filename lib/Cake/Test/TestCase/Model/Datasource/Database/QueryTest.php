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
class QueryTest extends \Cake\TestSuite\TestCase {


	public function setUp() {
		$this->connection = new Connection(Configure::read('Datasource.test'));
	}

	public function tearDown() {
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
 * Auxiliary function to insert a couple rows in a newly created table containing dates
 *
 * @return void
 **/
	protected function _insertDateRecords() {
		$table = 'CREATE TEMPORARY TABLE dates(id int, name varchar(50), posted datetime, visible char(1))';
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
					'id' => '3something-crazy',
					'posted <' => new \DateTime('2013-01-01 12:00')
				],
				['posted' => 'datetime', 'id' => 'boolean']
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
 *
 * @return void
 **/
	public function testSelectWhereNot() {
		$this->_insertDateRecords();
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

}
