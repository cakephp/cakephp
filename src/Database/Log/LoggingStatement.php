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
namespace Cake\Database\Log;

use Cake\Database\Statement\StatementDecorator;
use Exception;

/**
 * Statement decorator used to
 *
 * @internal
 */
class LoggingStatement extends StatementDecorator
{

    /**
     * Logger instance responsible for actually doing the logging task
     *
     * @var \Cake\Database\Log\QueryLogger
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
     * @param array|null $params List of values to be bound to query
     * @return bool True on success, false otherwise
     * @throws Exception Re-throws any exception raised during query execution.
     * @throws \Throwable Re-throws any errors raised during query execution.
     */
    public function execute($params = null)
    {
        $t = microtime(true);
        $query = new LoggedQuery();

        try {
            $result = parent::execute($params);
        } catch (\Throwable $e) {
            $e->queryString = $this->queryString;
            $query->error = $e;
            $this->_log($query, $params, $t);
            throw $e;
        } catch (Exception $e) {
            $e->queryString = $this->queryString;
            $query->error = $e;
            $this->_log($query, $params, $t);
            throw $e;
        }

        $query->numRows = $this->rowCount();
        $this->_log($query, $params, $t);
        return $result;
    }

    /**
     * Copies the logging data to the passed LoggedQuery and sends it
     * to the logging system.
     *
     * @param \Cake\Database\Log\LoggedQuery $query The query to log.
     * @param array $params List of values to be bound to query.
     * @param float $startTime The microtime when the query was executed.
     * @return void
     */
    protected function _log($query, $params, $startTime)
    {
        $query->took = round((microtime(true) - $startTime) * 1000, 0);
        $query->params = $params ?: $this->_compiledParams;
        $query->query = $this->queryString;
        $this->logger()->log($query);
    }

    /**
     * Wrapper for bindValue function to gather each parameter to be later used
     * in the logger function.
     *
     * @param string|int $column Name or param position to be bound
     * @param mixed $value The value to bind to variable in query
     * @param string|int|null $type PDO type or name of configured Type class
     * @return void
     */
    public function bindValue($column, $value, $type = 'string')
    {
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
     * @param object|null $instance Logger object instance.
     * @return object Logger instance
     */
    public function logger($instance = null)
    {
        if ($instance === null) {
            return $this->_logger;
        }
        return $this->_logger = $instance;
    }
}
