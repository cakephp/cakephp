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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Datasource\Paging;

/**
 * The pagination sort map.
 */
class SortMap
{
    /**
     * @var array<string, array<\Cake\Datasource\Paging\SortField>> The sort map items.
     */
    protected array $items = [];

    /**
     * @param string $key
     * @param \Cake\Datasource\Paging\SortField|string ...$fields
     * @return $this
     */
    public function push(string $key, SortField|string ...$fields)
    {
        $list = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $field = new SortField($field);
            }
            $list[] = $field;
        }
        $this->items[$key] = $list;

        return $this;
    }

    /**
     * Resolves sort mapping for a given sort key.
     *
     * Takes a sort key and resolves it using the sortMap configuration.
     * Supports simple mapping, multi-column sorting, and fixed direction sorting.
     *
     * @param string $sortKey The sort key to resolve
     * @param string $direction The requested sort direction
     * @param bool $directionSpecified Whether direction was explicitly specified
     * @return array<string, mixed>|null Returns resolved order array or null if key not found
     */
    public function resolve(
        string $sortKey,
        string $direction,
        bool $directionSpecified = true,
    ): ?array {
        $mapping = $this->items[$sortKey] ?? null;
        if ($mapping === null) {
            return null;
        }
        //Use key when not define fields
        if ($mapping === []) {
            return [$sortKey => $direction];
        }

        $order = [];
        foreach ($mapping as $sortField) {
            $field = $sortField->getField();
            $fieldDirection = $sortField->getDirection($direction, $directionSpecified);
            $order[$field] = $fieldDirection;
        }

        return $order;
    }
}
