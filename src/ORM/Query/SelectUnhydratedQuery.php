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
 * @since         5.next
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Query;

use Cake\ORM\Table;

/**
 * Non-hydrating SelectQuery variant. Always returns arrays.
 *
 * Behaves exactly like `SelectQuery->disableHydration()` at runtime — it is
 * fully substitutable for {@see SelectQuery} (eager loading, association
 * finders and the rest of the ORM may treat it like any other select query).
 * Its sole purpose is the static type: because it extends
 * `SelectQuery<array<string, mixed>>`, `first()` / `firstOrFail()` / `all()` /
 * `toArray()` / iteration resolve to arrays instead of `entity|array`, and
 * that binding survives finder dispatch where a bare generic annotation would
 * decay.
 *
 * Use {@see Table::findUnhydrated()} as the entry point. This class is the
 * type-safe replacement for `SelectQuery->disableHydration()`, which becomes
 * a hard error in 6.0.
 *
 * @extends \Cake\ORM\Query\SelectQuery<array<string, mixed>>
 */
class SelectUnhydratedQuery extends SelectQuery
{
    /**
     * @param \Cake\ORM\Table $table The table this query is starting on.
     */
    public function __construct(Table $table)
    {
        parent::__construct($table);
        $this->_hydrate = false;
    }
}
