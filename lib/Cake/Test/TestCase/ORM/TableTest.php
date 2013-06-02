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
 * Used to test correct class is instantiated when using Table::build();
 *
 **/
class DatesTable extends Table {

}

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
		Table::clearRegistry();
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

/**
 * Tests that table options can be pre-configured for the factory method
 *
 * @return void
 */
	public function testConfigAndBuild() {
		$map = Table::config();
		$this->assertEquals([], $map);

		$options = ['connection' => $this->connection];
		Table::config('things', $options);
		$map = Table::config();
		$this->assertEquals(['things' => $options], $map);
		$this->assertEquals($options, Table::config('things'));

		$schema = ['id' => ['rubbish']];
		$options += ['schema' => $schema];
		Table::config('things', $options);

		$table = Table::build('foo', ['table' => 'things']);
		$this->assertInstanceOf('Cake\ORM\Table', $table);
		$this->assertEquals('things', $table->table());
		$this->assertEquals('foo', $table->alias());
		$this->assertSame($this->connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());

		Table::clearRegistry();
		$this->assertEmpty(Table::config());

		$options['className'] = __NAMESPACE__ . '\DatesTable';
		Table::config('dates', $options);
		$table = Table::build('foo', ['table' => 'dates']);
		$this->assertInstanceOf(__NAMESPACE__ . '\DatesTable', $table);
		$this->assertEquals('dates', $table->table());
		$this->assertEquals('foo', $table->alias());
		$this->assertSame($this->connection, $table->connection());
		$this->assertEquals(array_keys($schema), $table->schema()->columns());
	}

	public function testInstance() {
		$this->assertNull(Table::instance('things'));
		$table = new Table(['table' => 'things']);
		Table::instance('things', $table);
		$this->assertSame($table, Table::instance('things'));
	}

	public function testFindAllNoFields() {
		$this->_createThingsTable();
		$table = new Table(['table' => 'things', 'connection' => $this->connection]);
		$results = $table->find('all')->toArray();
		$expected = [
			['id' => 1, 'title' => 'a title', 'body' => 'a body'],
			['id' => 2, 'title' => 'another title', 'body' => 'another body']
		];
		$this->assertSame($expected, $results);
	}

	public function testFindAllSomeFields() {
		$this->_createThingsTable();
		$table = new Table(['table' => 'things', 'connection' => $this->connection]);
		$results = $table->find('all')->select(['id', 'title'])->toArray();
		$expected = [
			['id' => 1, 'title' => 'a title'],
			['id' => 2, 'title' => 'another title']
		];
		$this->assertSame($expected, $results);

		$results = $table->find('all')->select(['id', 'foo' => 'title'])->toArray();
		$expected = [
			['id' => 1, 'foo' => 'a title'],
			['id' => 2, 'foo' => 'another title']
		];
		$this->assertSame($expected, $results);
	}

	public function testFindAllConditionAutoTypes() {
		$this->_createDatesTable();
		$table = new Table(['table' => 'dates', 'connection' => $this->connection]);
		$query = $table->find('all')
			->select(['id', 'name'])
			->where(['posted >=' => new \DateTime('2012-12-22 12:01')]);
		$expected = [
			['id' => 3, 'name' => 'Jet Li']
		];
		$this->assertSame($expected, $query->toArray());

		$query->orWhere(['dates.posted' => new \DateTime('2012-12-22 12:00')]);
		$expected = [
			['id' => 2, 'name' => 'Bruce Lee'],
			['id' => 3, 'name' => 'Jet Li']
		];
		$this->assertSame($expected, $query->toArray());
	}

/**
 * Tests that belongsTo() creates and configures correctly the association
 *
 * @return void
 */
	public function testBelongsTo() {
		$options = ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']];
		$table = new Table(['table' => 'dates']);
		$belongsTo = $table->belongsTo('user', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\BelongsTo', $belongsTo);
		$this->assertSame($belongsTo, $table->association('user'));
		$this->assertEquals('user', $belongsTo->name());
		$this->assertEquals('fake_id', $belongsTo->foreignKey());
		$this->assertEquals(['a' => 'b'], $belongsTo->conditions());
		$this->assertSame($table, $belongsTo->source());
	}

/**
 * Tests that hasOne() creates and configures correctly the association
 *
 * @return void
 */
	public function testHasOne() {
		$options = ['foreignKey' => 'user_id', 'conditions' => ['b' => 'c']];
		$table = new Table(['table' => 'users']);
		$hasOne = $table->hasOne('profile', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\HasOne', $hasOne);
		$this->assertSame($hasOne, $table->association('profile'));
		$this->assertEquals('profile', $hasOne->name());
		$this->assertEquals('user_id', $hasOne->foreignKey());
		$this->assertEquals(['b' => 'c'], $hasOne->conditions());
		$this->assertSame($table, $hasOne->source());
	}

/**
 * Tests that hasMany() creates and configures correctly the association
 *
 * @return void
 */
	public function testHasMany() {
		$options = [
			'foreignKey' => 'author_id',
			'conditions' => ['b' => 'c'],
			'sort' => ['foo' => 'asc']
		];
		$table = new Table(['table' => 'authors']);
		$hasMany = $table->hasMany('article', $options);
		$this->assertInstanceOf('\Cake\ORM\Association\HasMany', $hasMany);
		$this->assertSame($hasMany, $table->association('article'));
		$this->assertEquals('article', $hasMany->name());
		$this->assertEquals('author_id', $hasMany->foreignKey());
		$this->assertEquals(['b' => 'c'], $hasMany->conditions());
		$this->assertEquals(['foo' => 'asc'], $hasMany->sort());
		$this->assertSame($table, $hasMany->source());
	}

}
