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
namespace Cake\Database\Statement;

use Cake\Database\DriverInterface;
use Cake\Database\StatementInterface;

/**
 * Wraps a statement in a callback that allows row results
 * to be modified when being fetched.
 *
 * This is used by CakePHP to eagerly load association data.
 */
class CallbackStatement extends StatementDecorator
{
    /**
     * A callback function to be applied to results.
     *
     * @var callable
     */
    protected $_callback;

    /**
     * Constructor
     *
     * @param \Cake\Database\StatementInterface $statement The statement to decorate.
     * @param \Cake\Database\DriverInterface $driver The driver instance used by the statement.
     * @param callable $callback The callback to apply to results before they are returned.
     */
    public function __construct(StatementInterface $statement, DriverInterface $driver, callable $callback)
    {
        parent::__construct($statement, $driver);
        $this->_callback = $callback;
    }

    /**
     * Fetch a row from the statement.
     *
     * The result will be processed by the callback when it is not `false`.
     *
     * @param string|int $type Either 'num' or 'assoc' to indicate the result format you would like.
     * @return array|false
     */
    public function fetch($type = parent::FETCH_TYPE_NUM)
    {
        $callback = $this->_callback;
        $row = $this->_statement->fetch($type);

        return $row === false ? $row : $callback($row);
    }

    /**
     * Fetch all rows from the statement.
     *
     * Each row in the result will be processed by the callback when it is not `false.
     *
     * @param string|int $type Either 'num' or 'assoc' to indicate the result format you would like.
     * @return array
     */
    public function fetchAll($type = parent::FETCH_TYPE_NUM): array
    {
        /** @psalm-suppress PossiblyFalseArgument  */
        return array_map($this->_callback, $this->_statement->fetchAll($type));
    }
}
