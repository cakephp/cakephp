<?php
/**
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\InstanceConfigTrait;
use Cake\TestSuite\TestCase;

/**
 * TestInstanceConfig
 */
class TestInstanceConfig {

	use InstanceConfigTrait;

/**
 * _defaultConfig
 *
 * Some default config
 *
 * @var array
 */
	protected $_defaultConfig = [
		'some' => 'string',
		'a' => ['nested' => 'value']
	];
}

/**
 * ReadOnlyTestInstanceConfig
 */
class ReadOnlyTestInstanceConfig {

	use InstanceConfigTrait;

/**
 * _defaultConfig
 *
 * Some default config
 *
 * @var array
 */
	protected $_defaultConfig = [
		'some' => 'string',
		'a' => ['nested' => 'value']
	];

/**
 * Example of how to prevent modifying config at run time
 *
 * @throws \Exception
 * @param mixed $key
 * @param mixed $value
 * @return void
 */
	protected function _configWrite($key, $value = null) {
		throw new \Exception('This Instance is readonly');
	}

}

/**
 * InstanceConfigTraitTest
 *
 */
class InstanceConfigTraitTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->object = new TestInstanceConfig();
	}

/**
 * testDefaultsAreSet
 *
 * @return void
 */
	public function testDefaultsAreSet() {
		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'value']
			],
			$this->object->config(),
			'runtime config should match the defaults if not overriden'
		);
	}

/**
 * testGetSimple
 *
 * @return void
 */
	public function testGetSimple() {
		$this->assertSame(
			'string',
			$this->object->config('some'),
			'should return the key value only'
		);

		$this->assertSame(
			['nested' => 'value'],
			$this->object->config('a'),
			'should return the key value only'
		);
	}

/**
 * testGetDot
 *
 * @return void
 */
	public function testGetDot() {
		$this->assertSame(
			'value',
			$this->object->config('a.nested'),
			'should return the nested value only'
		);
	}

/**
 * testSetSimple
 *
 * @return void
 */
	public function testSetSimple() {
		$this->object->config('foo', 'bar');
		$this->assertSame(
			'bar',
			$this->object->config('foo'),
			'should return the same value just set'
		);

		$this->object->config('some', 'zum');
		$this->assertSame(
			'zum',
			$this->object->config('some'),
			'should return the overritten value'
		);

		$this->assertSame(
			[
				'some' => 'zum',
				'a' => ['nested' => 'value'],
				'foo' => 'bar',
			],
			$this->object->config(),
			'updates should be merged with existing config'
		);
	}

/**
 * testSetNested
 *
 * @return void
 */
	public function testSetNested() {
		$this->object->config('new.foo', 'bar');
		$this->assertSame(
			'bar',
			$this->object->config('new.foo'),
			'should return the same value just set'
		);

		$this->object->config('a.nested', 'zum');
		$this->assertSame(
			'zum',
			$this->object->config('a.nested'),
			'should return the overritten value'
		);

		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'zum'],
				'new' => ['foo' => 'bar']
			],
			$this->object->config(),
			'updates should be merged with existing config'
		);
	}

/**
 * testSetNested
 *
 * @return void
 */
	public function testSetArray() {
		$this->object->config(['foo' => 'bar']);
		$this->assertSame(
			'bar',
			$this->object->config('foo'),
			'should return the same value just set'
		);

		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'value'],
				'foo' => 'bar',
			],
			$this->object->config(),
			'updates should be merged with existing config'
		);

		$this->object->config(['new.foo' => 'bar']);
		$this->assertSame(
			'bar',
			$this->object->config('new.foo'),
			'should return the same value just set'
		);

		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'value'],
				'foo' => 'bar',
				'new' => ['foo' => 'bar']
			],
			$this->object->config(),
			'updates should be merged with existing config'
		);

		$this->object->config(['multiple' => 'different', 'a.values.to' => 'set']);

		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'value', 'values' => ['to' => 'set']],
				'foo' => 'bar',
				'new' => ['foo' => 'bar'],
				'multiple' => 'different'
			],
			$this->object->config(),
			'updates should be merged with existing config'
		);
	}

/**
 * testSetClobber
 *
 * @expectedException \Exception
 * @expectedExceptionMessage Cannot set a.nested.value
 * @return void
 */
	public function testSetClobber() {
		$this->object->config(['a.nested.value' => 'not possible']);
	}

/**
 * testReadOnlyConfig
 *
 * @expectedException \Exception
 * @expectedExceptionMessage This Instance is readonly
 * @return void
 */
	public function testReadOnlyConfig() {
		$object = new ReadOnlyTestInstanceConfig();

		$this->assertSame(
			[
				'some' => 'string',
				'a' => ['nested' => 'value']
			],
			$object->config(),
			'default config should be returned'
		);

		$object->config('throw.me', 'an exception');
	}

}
