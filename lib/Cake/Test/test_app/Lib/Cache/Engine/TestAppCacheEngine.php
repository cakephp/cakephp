<?php
/**
 * Test Suite Test App Cache Engine class.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.test_app.Lib.Cache.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestAppCacheEngine extends CacheEngine {

	public function write($key, $value, $duration) {
		if ($key == 'fail') {
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
}
