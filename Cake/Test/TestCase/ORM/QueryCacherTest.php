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

use Cake\TestSuite\TestCase;

/**
 * Query cacher test
 */
class QueryCacherTest extends TestCase {

/**
 * Setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->engine = $this->getMock('Cake\Cache\CacheEngine');
		Cache::config('queryCache', $this->engine);
	}

/**
 * Teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::drop('queryCache');
	}

/**
 * Test fetching with a function to generate the key.
 *
 * @return void
 */
	public function testFetchFunctionKey() {
	}

/**
 * Test fetching with a function to generate the key but the function is poop.
 *
 * @return void
 */
	public function testFetchFunctionKeyNoString() {
	}

/**
 * Test fetching with a cache instance.
 *
 * @return void
 */
	public function testFetchCacheInstance() {
	}

/**
 * Test fetching with a cache hit.
 *
 * @return void
 */
	public function testFetchCacheHit() {
	}

/**
 * Test fetching with a cache miss.
 *
 * @return void
 */
	public function testFetchCacheMiss() {
	}

}
