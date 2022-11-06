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
namespace Cake\ORM\Query;

use Cake\Database\Query\UpdateQuery as DbUpdateQuery;
use Cake\Database\ValueBinder;
use Cake\ORM\Table;

/**
 * @inheritDoc
 */
class UpdateQuery extends DbUpdateQuery
{
    use CommonQueryTrait;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct(Table $table)
    {
        parent::__construct($table->getConnection());

        $this->setRepository($table);
        $this->addDefaultTypes($table);
    }

    /**
     * @inheritDoc
     */
    public function sql(?ValueBinder $binder = null): string
    {
        if (empty($this->_parts['update'])) {
            $repository = $this->getRepository();
            $this->update($repository->getTable());
        }

        return parent::sql($binder);
    }
}
