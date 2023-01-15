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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Query;

use Cake\Database\Query;

/**
 * This class is used to generate DELETE queries for the relational database.
 */
class DeleteQuery extends Query
{
    /**
     * Type of this query.
     *
     * @var string
     */
    protected string $_type = self::TYPE_DELETE;

    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $_parts = [
        'comment' => null,
        'with' => [],
        'delete' => true,
        'modifier' => [],
        'from' => [],
        'join' => [],
        'where' => null,
        'order' => null,
        'limit' => null,
        'epilog' => null,
    ];

    /**
     * Create a delete query.
     *
     * Can be combined with from(), where() and other methods to
     * create delete queries with specific conditions.
     *
     * @param string|null $table The table to use when deleting.
     * @return $this
     */
    public function delete(?string $table = null)
    {
        $this->_dirty();
        if ($table !== null) {
            $this->from($table);
        }

        return $this;
    }
}
