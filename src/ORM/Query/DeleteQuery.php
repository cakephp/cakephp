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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Query;

use Cake\Database\Connection;
use Cake\Database\Query\DeleteQuery as DbDeleteQuery;
use Cake\Database\ValueBinder;
use Cake\ORM\Table;

class DeleteQuery extends DbDeleteQuery
{
    use CommonQueryTrait;

    /**
     * Constructor
     *
     * @param \Cake\Database\Connection $connection The connection object
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct(Connection $connection, Table $table)
    {
        parent::__construct($connection);

        $this->setRepository($table);
        $this->addDefaultTypes($table);
    }

    /**
     * @inheritDoc
     */
    public function sql(?ValueBinder $binder = null): string
    {
        if (empty($this->_parts['from'])) {
            $repository = $this->getRepository();
            $this->from([$repository->getAlias() => $repository->getTable()]);
        }

        return parent::sql($binder);
    }
}
