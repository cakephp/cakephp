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
use Cake\Core\Exception\CakeException;
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

    /**
     * Re-enabling hydration on a SelectUnhydratedQuery would violate its type
     * contract. Use {@see Table::find()} for entity results.
     *
     * @param bool $enable Must be false; passing true throws.
     * @return $this
     * @throws \BadMethodCallException When called with true.
     */
    public function enableHydration(bool $enable = true)
    {
        if ($enable) {
            throw new BadMethodCallException(
                'Cannot enable hydration on SelectUnhydratedQuery. Use Table::find() for entity results.',
            );
        }

        return $this;
    }

    /**
     * Apply a finder while keeping the non-hydrating contract.
     *
     * Same guard as {@see Table::findUnhydrated()}: a finder that discards the
     * passed query and returns a freshly built one cannot preserve the
     * non-hydrating shape, so it fails loudly here instead of yielding a
     * cryptic return-type error or a silently hydrated query.
     *
     * @param string $finder The finder method to use.
     * @param mixed ...$args Arguments that match up to finder-specific parameters.
     * @return static
     * @throws \Cake\Core\Exception\CakeException When the finder does not return the passed query.
     */
    public function find(string $finder, mixed ...$args): static
    {
        $result = $this->getRepository()->callFinder($finder, $this, ...$args);

        if (!$result instanceof static) {
            throw new CakeException(sprintf(
                'The `%s` finder must return the query it was given when chained on a %s; '
                . 'got `%s` instead. Finders that build a fresh query cannot preserve the '
                . 'non-hydrating contract — use find() on the table for those.',
                $finder,
                static::class,
                get_debug_type($result),
            ));
        }

        return $result;
    }
}
