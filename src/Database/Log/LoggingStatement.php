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
namespace Cake\Database\Log;

use Cake\Database\Statement\StatementDecorator;

/**
 * Statement decorator used to
 *
 * @return void
 */
class LoggingStatement extends StatementDecorator {

/**
 * Logger instance responsible for actually doing the logging task
 *
 * @var QueryLogger
 */
	protected $_logger;

/**
 * Holds bound params
 *
 * @var array
 */
	protected $_compiledParams = [];

/**
 * Wrapper for the execute function to calculate time spent
 * and log the query afterwards.
 *
 * @param array $params list of values to be bound to query
 * @return boolean true on success, false otherwise
 */
	public function execute($params = null) {
		$t = microtime(true);
		$result = parent::execute($params);

		$query = new LoggedQuery;
		$query->took = round((microtime(true) - $t) * 1000, 0);
		$query->numRows = $this->rowCount();
		$query->params = $params ?: $this->_compiledParams;
		$query->query = $this->queryString;
		$this->logger()->log($query);

		return $result;
	}

/**
 * Wrapper for bindValue function to gather each parameter to be later used
 * in the logger function.
 *
 * @param string|integer $column name or param position to be bound
 * @param mixed $value the value to bind to variable in query
 * @param string|integer $type PDO type or name of configured Type class
 * @return void
 */
	public function bindValue($column, $value, $type = 'string') {
		parent::bindValue($column, $value, $type);
		if ($type === null) {
			$type = 'string';
		}
		if (!ctype_digit($type)) {
			$value = $this->cast($value, $type)[0];
		}
		$this->_compiledParams[$column] = $value;
	}

/**
 * Sets the logger object instance. When called with no arguments
 * it returns the currently setup logger instance
 *
 * @param object $instance logger object instance
 * @return object logger instance
 */
	public function logger($instance = null) {
		if ($instance === null) {
			return $this->_logger;
		}
		return $this->_logger = $instance;
	}

}
