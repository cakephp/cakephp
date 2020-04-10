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

/**
 * Statement class meant to be used by an Sqlite driver
 *
 * @internal
 */
class SqliteStatement extends StatementDecorator
{
    use BufferResultsTrait;

    /**
     * @inheritDoc
     */
    public function execute(?array $params = null): bool
    {
        if ($this->_statement instanceof BufferedStatement) {
            $this->_statement = $this->_statement->getInnerStatement();
        }

        if ($this->_bufferResults) {
            $this->_statement = new BufferedStatement($this->_statement, $this->_driver);
        }

        return $this->_statement->execute($params);
    }

    /**
     * Returns the number of rows returned of affected by last execution
     *
     * @return int
     */
    public function rowCount(): int
    {
        /** @psalm-suppress NoInterfaceProperties */
        if (
            $this->_statement->queryString &&
            preg_match('/^(?:DELETE|UPDATE|INSERT)/i', $this->_statement->queryString)
        ) {
            $changes = $this->_driver->prepare('SELECT CHANGES()');
            $changes->execute();
            $row = $changes->fetch();
            $changes->closeCursor();

            if (!$row) {
                return 0;
            }

            return (int)$row[0];
        }

        return parent::rowCount();
    }
}
