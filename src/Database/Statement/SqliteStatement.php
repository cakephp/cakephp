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
class SqliteStatement extends Statement
{
    /**
     * @var int|null
     */
    protected ?int $affectedRows = null;

    /**
     * @inheritDoc
     */
    public function execute(?array $params = null): bool
    {
        $this->affectedRows = null;

        return parent::execute($params);
    }

    /**
     * @inheritDoc
     */
    public function rowCount(): int
    {
        if ($this->affectedRows !== null) {
            return $this->affectedRows;
        }

        if (
            $this->statement->queryString &&
            preg_match('/^(?:DELETE|UPDATE|INSERT)/i', $this->statement->queryString)
        ) {
            $changes = $this->_driver->prepare('SELECT CHANGES()');
            $changes->execute();
            $row = $changes->fetch();

            $this->affectedRows = $row ? (int)$row[0] : 0;
        } else {
            $this->affectedRows = parent::rowCount();
        }

        return $this->affectedRows;
    }
}
