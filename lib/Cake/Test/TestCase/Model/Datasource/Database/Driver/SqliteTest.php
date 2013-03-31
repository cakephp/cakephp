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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database\Driver;

use Cake\Core\Configure;
use Cake\Model\Datasource\Database\Driver\Sqlite;
use Cake\Testsuite\TestCase;
use \PDO;

/**
 * Tests Sqlite driver
 */
class SqliteTest extends TestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Sqlite') === false, 'Not using Sqlite for test config');
	}

/**
 * Test connecting to Sqlite with default configuration
 *
 * @return void
 */
	public function testConnectionConfigDefault() {
		$driver = $this->getMock('Cake\Model\Datasource\Database\driver\Sqlite', ['_connect']);
		$expected = [
			'persistent' => false,
			'database' => ':memory:',
			'encoding' => 'utf8',
			'login' => null,
			'password' => null,
			'flags' => [],
			'init' => [],
			'dsn' => 'sqlite::memory:'
		];

		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->connect([]);
	}

/**
 * Test connecting to Sqlite with custom configuration
 *
 * @return void
 */
	public function testConnectionConfigCustom() {
		$driver = $this->getMock('Cake\Model\Datasource\Database\driver\Sqlite', ['_connect', 'connection']);
		$config = [
			'persistent' => true,
			'host' => 'foo',
			'database' => 'bar.db',
			'flags' => [1 => true, 2 => false],
			'encoding' => 'a-language',
			'init' => ['Execute this', 'this too']
		];

		$expected = $config;
		$expected += ['login' => null, 'password' => null];
		$expected['dsn'] = 'sqlite:bar.db';
		$expected['flags'] += [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		$connection = $this->getMock('StdClass', ['exec']);
		$connection->expects($this->at(0))->method('exec')->with('Execute this');
		$connection->expects($this->at(1))->method('exec')->with('this too');
		$connection->expects($this->exactly(2))->method('exec');

		$driver->expects($this->once())->method('_connect')
			->with($expected);
		$driver->expects($this->any())->method('connection')
			->will($this->returnValue($connection));
		$driver->connect($config);
	}

/**
 * Dataprovider for column testing
 *
 * @return array
 */
	public static function columnProvider() {
		return [
			[
				'DATETIME',
				['datetime', null]
			],
			[
				'DATE',
				['date', null]
			],
			[
				'TIME',
				['time', null]
			],
			[
				'BOOLEAN',
				['boolean', null]
			],
			[
				'BIGINT',
				['biginteger', null]
			],
			[
				'VARCHAR(255)',
				['string', 255]
			],
			[
				'CHAR(25)',
				['string', 25]
			],
			[
				'BLOB',
				['binary', null]
			],
			[
				'INTEGER(11)',
				['integer', 11]
			],
			[
				'TINYINT(5)',
				['integer', 5]
			],
			[
				'MEDIUMINT(10)',
				['integer', 10]
			],
			[
				'FLOAT',
				['float', null]
			],
			[
				'DOUBLE',
				['float', null]
			],
			[
				'REAL',
				['float', null]
			],
			[
				'DECIMAL(11,2)',
				['decimal', null]
			],
		];
	}

/**
 * Test parsing SQLite column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = new Sqlite();
		$this->assertEquals($driver->convertColumn($input), $expected);
	}

}
