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
     * @var \Cake\Database\TypeMap
     */
    protected $_typeMap;

    /**
     * Creates a new TypeMap if $typeMap is an array, otherwise exchanges it for the given one.
     *
     * @param array|\Cake\Database\TypeMap $typeMap Creates a TypeMap if array, otherwise sets the given TypeMap
     * @return $this
     */
    public function setTypeMap($typeMap)
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
     * Overwrite the default type mappings for fields
     * in the implementing object.
     *
     * This method is useful if you need to set type mappings that are shared across
     * multiple functions/expressions in a query.
     *
     * To add a default without overwriting existing ones
     * use `getTypeMap()->addDefaults()`
     *
     * @param array $types The array of types to set.
     * @return $this
     * @see \Cake\Database\TypeMap::setDefaults()
     */
    public function setDefaultTypes(array $types)
    {
        $this->getTypeMap()->setDefaults($types);

        return $this;
    }

    /**
     * Gets default types of current type map.
     *
     * @return array
     */
    public function getDefaultTypes(): array
    {
        return $this->getTypeMap()->getDefaults();
    }
}
