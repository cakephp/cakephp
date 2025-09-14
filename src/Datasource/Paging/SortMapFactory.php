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
 * Factory for creating complete sortMap configurations.
 *
 * Provides fluent interface for building sortMaps with multiple sort keys.
 */
class SortMapFactory
{
    /**
     * @var array<string, array<\Cake\Datasource\Paging\SortField|string>> The sort map being built
     */
    protected array $map = [];

    /**
     * @var array<\Cake\Datasource\Paging\SortField> The current fields being built
     */
    protected array $fields = [];

    /**
     * @var string|null The current sort key being configured
     */
    protected ?string $currentKey = null;

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
     * Start defining a new sort key in the map.
     *
     * @param string $sortKey The sort key name
     * @return $this
     */
    public function sortKey(string $sortKey)
    {
        // Save current fields to map if we have a current key
        if ($this->currentKey !== null) {
            if (!empty($this->fields)) {
                $this->map[$this->currentKey] = $this->fields;
            } elseif (!isset($this->map[$this->currentKey])) {
                // If no fields were added, use the key as the field name
                $this->map[$this->currentKey] = [$this->currentKey];
            }
            $this->fields = [];
        }

        $this->currentKey = $sortKey;

        return $this;
    }

    /**
     * Add a field with ascending default direction.
     *
     * @param string $field The field name
     * @return $this
     */
    public function asc(string $field)
    {
        $this->fields[] = SortField::asc($field);

        return $this;
    }

    /**
     * Add a field with descending default direction.
     *
     * @param string $field The field name
     * @return $this
     */
    public function desc(string $field)
    {
        $this->fields[] = SortField::desc($field);

        return $this;
    }

    /**
     * Add a locked field with fixed direction.
     *
     * @param string $field The field name
     * @param string $direction The fixed direction (SortField::ASC or SortField::DESC)
     * @return $this
     */
    public function locked(string $field, string $direction)
    {
        $this->fields[] = SortField::locked($field, $direction);

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
     * Add a plain string field (for backward compatibility).
     *
     * @param string $field The field name
     * @return $this
     */
    public function string(string $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Build and return the complete sortMap.
     *
     * @return array<string, array<\Cake\Datasource\Paging\SortField|string>>
     */
    public function build(): array
    {
        // Save any pending fields
        if ($this->currentKey !== null) {
            if (!empty($this->fields)) {
                $this->map[$this->currentKey] = $this->fields;
            } elseif (!isset($this->map[$this->currentKey])) {
                // If no fields were added, use the key as the field name
                $this->map[$this->currentKey] = [$this->currentKey];
            }
        }

        return $this->map;
    }
}
