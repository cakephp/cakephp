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
namespace Cake\ORM;

use Cake\Collection\Collection;
use Cake\Datasource\ResultSetInterface;

/**
 * Represents the results obtained after executing a query for a specific table
 * This object is responsible for correctly nesting result keys reported from
 * the query, casting each field to the correct type and executing the extra
 * queries required for eager loading external associations.
 *
 * @template T
 * @implements \Cake\Datasource\ResultSetInterface<T>
 */
class ResultSet extends Collection implements ResultSetInterface
{
    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $key = $this->key();
        $items = $this->toArray();

        $this->rewind();
        // Move the internal pointer to the previous position otherwise it creates problems with Xdebug
        // https://github.com/cakephp/cakephp/issues/18234
        while ($this->key() !== $key) {
            $this->next();
        }

        return [
            'items' => $items,
        ];
    }
}
