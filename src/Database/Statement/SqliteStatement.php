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

use Cake\Database\Statement;

/**
 * Statement class meant to be used by an Sqlite driver
 *
 * @internal
 */
class SqliteStatement extends Statement
{
    protected ?int $affectedRows = null;

    /**
     * @inheritDoc
     */
    public function rowCount(): int
    {
        if ($this->affectedRows !== null) {
            return $this->affectedRows;
        }

        if ($this->typeConverter) {
            // Use default implementation for select queries
            return parent::rowCount();
        }

        $changesQuery = $this->driver->prepare('SELECT CHANGES()');
        $changesQuery->execute();
        $result = $changesQuery->fetch(PDO::FETCH_NUM);
        $changesQuery->closeCursor();

        return $this->affectedRows = $result ? (int)$result[0] : 0;
    }
}
