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
namespace Cake\Test\TestCase\Database\Schema\Dialect;

use Cake\Core\Configure;
use Cake\Database\Schema\Dialect\Mysql;
use Cake\TestSuite\TestCase;


/**
 * Test case for Mysql Schema Dialect.
 */
class MysqlTest extends TestCase {

/**
 * Helper method for skipping tests that need a real connection.
 *
 * @return void
 */
	protected function _needsConnection() {
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Mysql') === false, 'Not using Mysql for test config');
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
				'TINYINT(1)',
				['boolean', null]
			],
			[
				'TINYINT(2)',
				['integer', 2]
			],
			[
				'INTEGER(11)',
				['integer', 11]
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
				'TINYTEXT',
				['string', null]
			],
			[
				'BLOB',
				['binary', null]
			],
			[
				'MEDIUMBLOB',
				['binary', null]
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
				'DECIMAL(11,2)',
				['decimal', null]
			],
		];
	}

/**
 * Test parsing MySQL column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new Mysql($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
	}

/**
 * Provider for testing index conversion
 *
 * @return array
 */
	public static function convertIndexProvider() {
		return [
			['PRI', 'primary'],
			['UNI', 'unique'],
			['MUL', 'index'],
		];
	}
/**
 * Test parsing MySQL index types.
 *
 * @dataProvider convertIndexProvider
 * @return void
 */
	public function testConvertIndex($input, $expected) {
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new Mysql($driver);
		$this->assertEquals($expected, $dialect->convertIndex($input));
	}

}
