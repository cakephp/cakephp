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

/*
 * Represents a class that holds a TypeMap object
 */
/**
 * Trait TypeMapTrait
 */
trait TypeMapTrait
{
    /**
     * @var \Cake\Database\TypeMap|null
     */
    protected ?TypeMap $_typeMap = null;

    /**
     * Creates a new TypeMap if $typeMap is an array, otherwise exchanges it for the given one.
     *
     * @param \Cake\Database\TypeMap|array $typeMap Creates a TypeMap if array, otherwise sets the given TypeMap
     * @return $this
     */
    public function setTypeMap(TypeMap|array $typeMap)
    {
        $this->_typeMap = is_array($typeMap) ? new TypeMap($typeMap) : $typeMap;

        return $this;
    }

    /**
     * Returns the existing type map.
     *
     * @return \Cake\Database\TypeMap
     */
    public function getTypeMap(): TypeMap
    {
        if ($this->_typeMap === null) {
            $this->_typeMap = new TypeMap();
        }

        return $this->_typeMap;
    }

    /**
     * Sets types for names or aliases.
     *
     * If a field is already mapped, it is replaced.
     *
     * @param array<string, string> $types Map of fields to types
     * @return $this
     */
    public function setTypes(array $types)
    {
        $this->getTypeMap()->setTypes($types);

        return $this;
    }

    /**
     * Gets map of fields to types.
     *
     * @return array<string, string>
     */
    public function getTypes(): array
    {
        return $this->getTypeMap()->getTypes();
    }
}
