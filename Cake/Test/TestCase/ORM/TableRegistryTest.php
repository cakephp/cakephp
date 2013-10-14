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

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Test case for TableRegistry
 */
class TableRegistryTest extends TestCase {

/**
 * tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Test config() method.
 *
 * @return void
 */
	public function testConfig() {
		$this->assertEquals([], TableRegistry::config('Test'));

		$data = [
			'connection' => 'testing',
			'entityClass' => 'TestApp\Model\Entity\Article',
		];
		$result = TableRegistry::config('Test', $data);
		$this->assertEquals($data, $result, 'Returns config data.');

		$result = TableRegistry::config();
		$expected = ['Test' => $data];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting instances from the registry.
 *
 * @return void
 */
	public function testGet() {
		$result = TableRegistry::get('Article', [
			'table' => 'my_articles',
		]);
		$this->assertInstanceOf('Cake\ORM\Table', $result);
		$this->assertEquals('my_articles', $result->table());

		$result2 = TableRegistry::get('Article', [
			'table' => 'herp_derp',
		]);
		$this->assertSame($result, $result2);
		$this->assertEquals('my_articles', $result->table());
	}

/**
 * Test that get() uses config data set with config()
 *
 * @return void
 */
	public function testGetWithConfig() {
		TableRegistry::config('Article', [
			'table' => 'my_articles',
		]);
		$result = TableRegistry::get('Article');
		$this->assertEquals('my_articles', $result->table(), 'Should use config() data.');
	}

/**
 * Test setting an instance.
 *
 * @return void
 */
	public function testSet() {
		$mock = $this->getMock('Cake\ORM\Table');
		$this->assertSame($mock, TableRegistry::set('Article', $mock));
		$this->assertSame($mock, TableRegistry::get('Article'));
	}

}
