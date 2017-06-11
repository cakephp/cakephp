<?php
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

use PDO;

/**
 * Statement class meant to be used by a Mysql PDO driver
 *
 * @internal
 */
class MysqlStatement extends PDOStatement
{

    use BufferResultsTrait;

    /**
     * {@inheritDoc}
     *
     */
    public function execute($params = null)
    {
        $connection = $this->_driver->connection();

        try {
            $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $this->_bufferResults);
            $result = $this->_statement->execute($params);
        } finally {
            $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        return $result;
    }
}
