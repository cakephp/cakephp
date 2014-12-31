<?php
/**
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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Connection;
use Cake\Database\Expression\TableNameExpression;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests TableNameExpression class
 *
 */
class TableNameExpressionTest extends TestCase {

/**
 * Tests getting and setting the name
 *
 * @return void
 */
	public function testGetAndSetName() {
		$expression = new TableNameExpression('foo', '');
		$this->assertEquals('foo', $expression->getValue());
		$expression->setValue('bar');
		$this->assertEquals('bar', $expression->getValue());
	}

/**
 * Tests getting and setting the name
 *
 * @return void
 */
	public function testGetAndSetPrefix() {
		$expression = new TableNameExpression('foo', '');
		$this->assertEquals('', $expression->getPrefix());
		$expression->setPrefix('prefix_');
		$this->assertEquals('prefix_', $expression->getPrefix());
	}

/**
 * Tests converting to sql
 *
 * @return void
 */
	public function testSQL() {
		$expression = new TableNameExpression('foo', '');
		$this->assertEquals('foo', $expression->sql(new ValueBinder));

		$expression = new TableNameExpression('foo', 'prefix_');
		$this->assertEquals('prefix_foo', $expression->sql(new ValueBinder));

		$driver = $this->getMock('Cake\Database\Driver\Sqlite', ['enabled']);
		$driver->expects($this->once())
			->method('enabled')
			->will($this->returnValue(true));
		$connection = new Connection(['driver' => $driver]);

		$name = "foo";
		$expression = new TableNameExpression($name, 'prefix_');
		$quoted = $connection->quoteIdentifier($expression->getValue());
		$expression->setValue($quoted);
		$expression->setQuoted();
		$this->assertQuotedString('<prefix_foo>', $expression->sql(new ValueBinder));

		$name = "bar";
		$expression = new TableNameExpression($name, '');
		$quoted = $connection->quoteIdentifier($expression->getValue());
		$expression->setValue($quoted);
		$this->assertQuotedString('<bar>', $expression->sql(new ValueBinder));
	}

/**
 * Assertion for comparing a regex pattern against a table name having its identifiers
 * quoted. It accepts string quoted with the characters `<` and `>`. If the third
 * parameter is set to true, it will alter the pattern to both accept quoted and
 * unquoted queries
 *
 * @param string $pattern
 * @param string $string the result to compare against
 * @param bool $optional
 * @return void
 */
	public function assertQuotedString($pattern, $string, $optional = false) {
		if ($optional) {
			$optional = '?';
		}
		$pattern = str_replace('<', '[`"\[]' . $optional, $pattern);
		$pattern = str_replace('>', '[`"\]]' . $optional, $pattern);
		$this->assertRegExp('#' . $pattern . '#', $string);
	}

}
