<?php
/**
 * Test Suite Test App Cache Engine class.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Lib.Cache.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestAppCacheEngine
 *
 * @package       Cake.Test.TestApp.Lib.Cache.Engine
 */
class TestAppCacheEngine extends CacheEngine {

	public function write($key, $value, $duration) {
		if ($key === 'fail') {
			return false;
		}
	}

	public function read($key) {
	}

	public function increment($key, $offset = 1) {
	}

	public function decrement($key, $offset = 1) {
	}

	public function delete($key) {
	}

	public function clear($check) {
	}

	public function clearGroup($group) {
	}

	public function add($key, $value, $duration) {
	}
}
