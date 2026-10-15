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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Attribute\Declaration;

use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;

/**
 * Records whether declarations run after table setup and association registration.
 */
class ObservedBehavior extends Behavior
{
    /**
     * Captures the table state seen by a declared behavior.
     *
     * @param array<string, mixed> $config Runtime behavior configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setConfig('tableAtInitialization', $this->table->getTable());
        $this->setConfig('associationAtInitialization', $this->table->hasAssociation('Users'));
    }

    /**
     * Supplies a finder that must be registered by the normal behavior registry.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query being configured.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findDeclared(SelectQuery $query): SelectQuery
    {
        return $query;
    }
}
