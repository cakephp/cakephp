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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Traits;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

/**
 * Provides a set of methods that allow tests cases to test queries with connection prefix
 */
trait ConnectionPrefixTestTrait {

/**
 * Prefix of the 'test' Connection
 *
 * @var string
 */
	public $prefix = '';

/**
 * Set the connection prefix
 *
 * @return void
 */
	public function setPrefix() {
		$config = ConnectionManager::config('test');
		if (isset($config['prefix']) && $config['prefix'] !== '') {
			$this->prefix = $config['prefix'];
		}
	}

/**
 * Gets the connection prefix of an instance of \Cake\Database\Connection
 *
 * @param \Cake\Database\Connection $connection Instance of Connection
 *
 * @return string Connection prefix
 */
	protected function _getConnectionPrefix(\Cake\Database\Connection $connection) {
		$config = $connection->config();
		$prefix = isset($config["prefix"]) && is_string($config["prefix"]) ? $config["prefix"] : "";

		return $prefix;
	}

/**
 * Will apply connection prefix to a raw SQL query.
 * Prefixes are to be represented by the character ~
 *
 * @param string $query Query as a string that should be prefixed
 * @return string The given query with the connection prefix, if any
 */
	public function applyConnectionPrefix($query) {
		$query = str_replace('~', $this->prefix, $query);
		return $query;
	}
}