<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Log;

use Cake\Database\Statement\StatementDecorator;
use Exception;
use Psr\Log\LoggerInterface;

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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Holds bound params
     *
     * @var array
     */
    protected $_compiledParams = [];

    /**
     * Query execution start time.
     *
     * @var float
     */
    protected $startTime = 0.0;

    /**
     * Logged query
     *
     * @var \Cake\Database\Log\LoggedQuery|null
     */
    protected $loggedQuery;

    /**
     * Wrapper for the execute function to calculate time spent
     * and log the query afterwards.
     *
     * @param array|null $params List of values to be bound to query
     * @return bool True on success, false otherwise
     * @throws \Exception Re-throws any exception raised during query execution.
     */
    public function execute(?array $params = null): bool
    {
        $this->startTime = microtime(true);

        $this->loggedQuery = new LoggedQuery();
        $this->loggedQuery->params = $params ?: $this->_compiledParams;

        try {
            $result = parent::execute($params);
            $this->loggedQuery->took = (int)round((microtime(true) - $this->startTime) * 1000, 0);
        } catch (Exception $e) {
            /** @psalm-suppress UndefinedPropertyAssignment */
            $e->queryString = $this->queryString;
            $this->loggedQuery->error = $e;
            $this->_log();
            throw $e;
        }

        if (preg_match('/^(?!SELECT)/i', $this->queryString)) {
            $this->rowCount();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function fetch($type = self::FETCH_TYPE_NUM)
    {
        $record = parent::fetch($type);

        if ($this->loggedQuery) {
            $this->rowCount();
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function fetchAll($type = self::FETCH_TYPE_NUM)
    {
        $results = parent::fetchAll($type);

        if ($this->loggedQuery) {
            $this->rowCount();
        }

        return $results;
    }

    /**
     * @inheritDoc
     */
    public function rowCount(): int
    {
        $result = parent::rowCount();

        if ($this->loggedQuery) {
            $this->loggedQuery->numRows = $result;
            $this->_log();
        }

        return $result;
    }

    /**
     * Copies the logging data to the passed LoggedQuery and sends it
     * to the logging system.
     *
     * @return void
     */
    protected function _log(): void
    {
        if ($this->loggedQuery === null) {
            return;
        }

        $this->loggedQuery->query = $this->queryString;
        $this->getLogger()->debug((string)$this->loggedQuery, ['query' => $this->loggedQuery]);

        $this->loggedQuery = null;
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
    public function bindValue($column, $value, $type = 'string'): void
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
     * Sets a logger
     *
     * @param \Psr\Log\LoggerInterface $logger Logger object
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

    /**
     * Gets the logger object
     *
     * @return \Psr\Log\LoggerInterface logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->_logger;
    }
}
