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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

/**
 * Maps fields to types.
 */
class TypeMap
{
    /**
     * @var array<string>
     */
    protected array $_types;

    /**
     * @param array<string> $types Map of fields to types
     */
    public function __construct(array $types = [])
    {
        $this->_types = $types;
    }

    /**
     * Sets types for fields.
     *
     * If a field is already mapped, the type is replaced.
     *
     * Example:
     * ```
     * $typeMap->setTypes(['id' => 'integer']);
     * ```
     *
     * @param array<string> $types Map of fields to types
     * @return $this
     */
    public function setTypes(array $types)
    {
        $this->_types = array_merge($this->_types, $types);

        return $this;
    }

    /**
     * Gets map of fields to types.
     *
     * @return array<string>
     */
    public function getTypes(): array
    {
        return $this->_types;
    }

    /**
     * Returns type for a specific field or null if not set.
     *
     * @param string|int $field Field name to map
     * @return string|null
     */
    public function type(string|int $field): ?string
    {
        return $this->_types[$field] ?? null;
    }
}
