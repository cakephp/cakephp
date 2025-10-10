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
 * Factory for creating complete sorts configurations.
 *
 * Provides non-fluent interface for building sorts with multiple sort keys and fields.
 */
class SortsFactory
{
    /**
     * @var array<string, array<\Cake\Datasource\Paging\SortField|string>> The sorts map being built
     */
    protected array $map = [];

    /**
     * Add a sort key with its associated SortField objects.
     *
     * @param string $sortKey The sort key name
     * @param \Cake\Datasource\Paging\SortField|string ...$fields The sort fields to add
     * @return $this
     */
    public function add(string $sortKey, SortField|string ...$fields)
    {
        if ($fields === []) {
            // If no fields provided, use the key as the field name
            $this->map[$sortKey] = [$sortKey];
        } else {
            $this->map[$sortKey] = $fields;
        }

        return $this;
    }

    /**
     * Build and return the complete sorts map.
     *
     * @return array<string, array<\Cake\Datasource\Paging\SortField|string>>
     */
    public function build(): array
    {
        return $this->map;
    }
}
