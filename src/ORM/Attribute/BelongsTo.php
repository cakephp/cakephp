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
namespace Cake\ORM\Attribute;

use Attribute;

/**
 * Declares a BelongsTo association on a table class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class BelongsTo extends Association
{
    /**
     * Stores the target and statically declared association options.
     *
     * @param class-string<\Cake\ORM\Table> $className Target table implementation.
     * @param string|null $alias Explicit association alias.
     * @param array<string>|string|false|null $foreignKey Foreign key columns, or false to disable the key.
     * @param array<string>|string|null $bindingKey Referenced key columns.
     * @param string|null $propertyName Entity property used for associated records.
     * @param string|null $joinType SQL join type.
     * @param string|null $strategy Association loading strategy.
     * @param array|string|null $finder Finder name and optional arguments.
     * @param array $conditions Static association conditions.
     */
    public function __construct(
        string $className,
        ?string $alias = null,
        array|string|false|null $foreignKey = null,
        array|string|null $bindingKey = null,
        ?string $propertyName = null,
        ?string $joinType = null,
        ?string $strategy = null,
        array|string|null $finder = null,
        array $conditions = [],
    ) {
        parent::__construct($className, $alias, array_filter([
            'foreignKey' => $foreignKey,
            'bindingKey' => $bindingKey,
            'propertyName' => $propertyName,
            'joinType' => $joinType,
            'strategy' => $strategy,
            'finder' => $finder,
            'conditions' => $conditions,
        ], static fn(mixed $value): bool => $value !== null));
    }

    /**
     * Returns the existing table method used to create this association.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'belongsTo';
    }
}
