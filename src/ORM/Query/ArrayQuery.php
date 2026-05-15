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

use BadMethodCallException;
use Cake\ORM\Table;

/**
 * Non-hydrating SelectQuery variant. Always returns arrays.
 *
 * Construction methods (where/join/order/contain/finders/eager loading) behave
 * identically to {@see SelectQuery}. Only the result shape differs — first(),
 * firstOrFail(), all(), toArray() and iteration produce arrays instead of
 * entities. Bind a finder once, get array results forever; the type system
 * knows the difference.
 *
 * Use {@see Table::findUnhydrated()} as the entry point. This class is the
 * type-safe replacement for `SelectQuery->disableHydration()`, which becomes
 * a hard error in 6.0.
 *
 * @extends \Cake\ORM\Query\SelectQuery<array<string, mixed>>
 */
class ArrayQuery extends SelectQuery
{
    /**
     * @param \Cake\ORM\Table $table The table this query is starting on.
     */
    public function __construct(Table $table)
    {
        parent::__construct($table);
        $this->_hydrate = false;
    }

    /**
     * Re-enabling hydration on an ArrayQuery would violate its type contract.
     * Use {@see Table::find()} for entity results.
     *
     * @param bool $enable Must be false; passing true throws.
     * @return $this
     * @throws \BadMethodCallException When called with true.
     */
    public function enableHydration(bool $enable = true)
    {
        if ($enable) {
            throw new BadMethodCallException(
                'Cannot enable hydration on ArrayQuery. Use Table::find() for entity results.',
            );
        }

        return $this;
    }
}
