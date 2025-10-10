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
 * Factory for creating SortField configurations.
 *
 * Provides fluent interface for building sort field collections.
 */
class SortFieldFactory
{
    /**
     * @var array<\Cake\Datasource\Paging\SortField> The sort fields being built
     */
    protected array $fields = [];

    /**
     * Create a new factory instance.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a field with ascending default direction.
     *
     * @param string $field The field name
     * @param bool $locked Whether the sort direction is locked
     * @return $this
     */
    public function asc(string $field, bool $locked = false)
    {
        $this->fields[] = SortField::asc($field, locked: $locked);

        return $this;
    }

    /**
     * Add a field with descending default direction.
     *
     * @param string $field The field name
     * @param bool $locked Whether the sort direction is locked
     * @return $this
     */
    public function desc(string $field, bool $locked = false)
    {
        $this->fields[] = SortField::desc($field, locked: $locked);

        return $this;
    }



    /**
     * Add a custom SortField instance.
     *
     * @param \Cake\Datasource\Paging\SortField $sortField The sort field to add
     * @return $this
     */
    public function add(SortField $sortField)
    {
        $this->fields[] = $sortField;

        return $this;
    }

    /**
     * Add a toggleable field with optional default direction.
     *
     * @param string $field The field name
     * @param string|null $defaultDirection The default direction or null
     * @return $this
     */
    public function field(string $field, ?string $defaultDirection = null)
    {
        $this->fields[] = new SortField($field, $defaultDirection, false);

        return $this;
    }

    /**
     * Build and return the array of SortField objects.
     *
     * @return array<\Cake\Datasource\Paging\SortField>
     */
    public function build(): array
    {
        return $this->fields;
    }
}
