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
namespace Cake\ORM;

use Cake\ORM\Query;

/**
 * Handles caching queries and loading results from the cache.
 *
 * Used by Cake\ORM\Query internally.
 *
 * @see Cake\ORM\Query::cache() for the public interface.
 */
class QueryCacher {

/**
 * Constructor.
 *
 * @param string|Closure $key
 * @param string|CacheEngine $config
 */
	public function __construct($key, $config) {
		$this->_key = $key;
		$this->_config = $config;
	}

/**
 * Load the cached results from the cache or run the query.
 *
 * @param Query $query The query the cache read is for.
 * @return ResultSet|null Either the cached results or null.
 */
	public function fetch(Query $query) {
	}

}
