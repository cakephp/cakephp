<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\StaticConfigTrait;
use Cake\TestSuite\TestCase;
use PHPUnit_Framework_Test;

/**
 * StaticConfigTraitTest class
 *
 */
class StaticConfigTraitTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->subject = $this->getObjectForTrait('Cake\Core\StaticConfigTrait');
	}

	public function tearDown() {
		unset($this->subject);
		parent::tearDown();
	}

/**
 * Tests simple usage of parseDsn
 *
 * @return void
 */
	public function testSimpleParseDsn() {
		$klassName = get_class($this->subject);

		$this->assertInternalType('string', $klassName::parseDsn(''));
		$this->assertEquals('', $klassName::parseDsn(''));

		$this->assertInternalType('array', $klassName::parseDsn(['key' => 'value']));
		$this->assertEquals(['key' => 'value'], $klassName::parseDsn(['key' => 'value']));

		$this->assertInternalType('array', $klassName::parseDsn(['url' => 'http://:80']));
		$this->assertEquals(['url' => 'http://:80'], $klassName::parseDsn(['url' => 'http://:80']));

		$this->assertInternalType('array', $klassName::parseDsn(['url' => 'http://user@:80']));
		$this->assertEquals(['url' => 'http://user@:80'], $klassName::parseDsn(['url' => 'http://user@:80']));

		$dsn = 'mysql://localhost:3306/database';
		$expected = [
			'className' => 'mysql',
			'driver' => 'mysql',
			'host' => 'localhost',
			'path' => '/database',
			'port' => 3306,
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'mysql://user:password@localhost:3306/database';
		$expected = [
			'className' => 'mysql',
			'driver' => 'mysql',
			'host' => 'localhost',
			'password' => 'password',
			'path' => '/database',
			'port' => 3306,
			'username' => 'user',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Sqlite:///memory:';
		$expected = [
			'className' => 'Cake\Database\Driver\Sqlite',
			'driver' => 'Cake\Database\Driver\Sqlite',
			'path' => '/memory:',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Sqlite:///?database=memory:';
		$expected = [
			'className' => 'Cake\Database\Driver\Sqlite',
			'driver' => 'Cake\Database\Driver\Sqlite',
			'database' => 'memory:',
			'path' => '/',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Sqlserver://sa:Password12!@.\SQL2012SP1/cakephp?MultipleActiveResultSets=false';
		$expected = [
			'className' => 'Cake\Database\Driver\Sqlserver',
			'driver' => 'Cake\Database\Driver\Sqlserver',
			'host' => '.\SQL2012SP1',
			'MultipleActiveResultSets' => false,
			'password' => 'Password12!',
			'path' => '/cakephp',
			'username' => 'sa',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));
	}

/**
 * Tests className/driver value setting
 *
 * @return void
 */
	public function testParseDsnClassnameDriver() {
		$klassName = get_class($this->subject);


		$dsn = 'Cake\Database\Driver\Mysql://localhost:3306/database';
		$expected = [
			'className' => 'Cake\Database\Driver\Mysql',
			'driver' => 'Cake\Database\Driver\Mysql',
			'host' => 'localhost',
			'path' => '/database',
			'port' => 3306,
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Mysql://user:password@localhost:3306/database';
		$expected = [
			'className' => 'Cake\Database\Driver\Mysql',
			'driver' => 'Cake\Database\Driver\Mysql',
			'host' => 'localhost',
			'password' => 'password',
			'path' => '/database',
			'port' => 3306,
			'username' => 'user',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Mysql://localhost/database?className=Cake\Database\Connection';
		$expected = [
			'className' => 'Cake\Database\Connection',
			'driver' => 'Cake\Database\Driver\Mysql',
			'host' => 'localhost',
			'path' => '/database',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Driver\Mysql://localhost:3306/database?className=Cake\Database\Connection';
		$expected = [
			'className' => 'Cake\Database\Connection',
			'driver' => 'Cake\Database\Driver\Mysql',
			'host' => 'localhost',
			'path' => '/database',
			'port' => 3306,
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'Cake\Database\Connection://localhost:3306/database?driver=Cake\Database\Driver\Mysql';
		$expected = [
			'className' => 'Cake\Database\Connection',
			'driver' => 'Cake\Database\Driver\Mysql',
			'host' => 'localhost',
			'path' => '/database',
			'port' => 3306,
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));
	}

/**
 * Tests parsing querystring values
 *
 * @return void
 */
	public function testParseDsnQuerystring() {
		$klassName = get_class($this->subject);

		$expected = [
			'className' => 'Cake\Log\Engine\FileLog',
			'driver' => 'Cake\Log\Engine\FileLog',
			'url' => 'test',
			'path' => '/',
		];
		$dsn = 'Cake\Log\Engine\FileLog:///?url=test';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$expected = [
			'className' => 'Cake\Log\Engine\FileLog',
			'driver' => 'Cake\Log\Engine\FileLog',
			'file' => 'debug',
			'path' => '/',
			'key' => 'value',
		];
		$dsn = 'Cake\Log\Engine\FileLog:///?file=debug&key=value';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$expected = [
			'className' => 'Cake\Log\Engine\FileLog',
			'driver' => 'Cake\Log\Engine\FileLog',
			'file' => 'debug',
			'path' => '/tmp',
			'types' => ['notice', 'info', 'debug'],
		];
		$dsn = 'Cake\Log\Engine\FileLog:///tmp?file=debug&types[]=notice&types[]=info&types[]=debug';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$expected = [
			'className' => 'Mail',
			'client' => null,
			'driver' => 'Mail',
			'key' => true,
			'key2' => false,
			'path' => '/',
			'timeout' =>'30',
			'tls' => null,
		];
		$dsn = 'Mail:///?timeout=30&key=true&key2=false&client=null&tls=null';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$expected = [
			'className' => 'Mail',
			'client' => null,
			'driver' => 'Mail',
			'host' => 'null',
			'key' => true,
			'key2' => false,
			'password' => 'false',
			'path' => '/1',
			'timeout' =>'30',
			'tls' => null,
			'username' => 'true',
		];
		$dsn = 'Mail://true:false@null/1?timeout=30&key=true&key2=false&client=null&tls=null';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$expected = [
			'className' => 'Mail',
			'client' => null,
			'driver' => 'Mail',
			'host' => 'localhost',
			'password' => 'secret',
			'port' => 25,
			'timeout' =>'30',
			'tls' => null,
			'username' => 'user',
		];
		$dsn = 'Mail://user:secret@localhost:25?timeout=30&client=null&tls=null';
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'File:///?prefix=myapp_cake_core_&serialize=true&duration=%2B2 minutes';
		$expected = [
			'className' => 'File',
			'driver' => 'File',
			'duration' => '+2 minutes',
			'path' => '/',
			'prefix' => 'myapp_cake_core_',
			'serialize' => true,
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));
	}

/**
 * Tests loading a single plugin
 *
 * @return void
 */
	public function testParseDsnPathSetting() {
		$klassName = get_class($this->subject);

		$dsn = 'File:///';
		$expected = [
			'className' => 'File',
			'driver' => 'File',
			'path' => '/',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));

		$dsn = 'File:///?path=/tmp/persistent/';
		$expected = [
			'className' => 'File',
			'driver' => 'File',
			'path' => '/tmp/persistent/',
		];
		$this->assertEquals($expected, $klassName::parseDsn(['url' => $dsn]));
	}

}

