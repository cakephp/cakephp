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
 * Type converter trait
 */
trait TypeConverterTrait
{
    /**
     * Converts a give value to a suitable database value based on type
     * and return relevant internal statement type
     *
     * @param mixed $value The value to cast
     * @param \Cake\Database\TypeInterface|string|int $type The type name or type instance to use.
     * @return array list containing converted value and internal type
     * @pslam-return array{mixed, int}
     */
    public function cast($value, $type = 'string'): array
    {
        if (is_string($type)) {
            $type = TypeFactory::build($type);
        }
        if ($type instanceof TypeInterface) {
            $value = $type->toDatabase($value, $this->getDriver());
            $type = $type->toStatement($value, $this->getDriver());
        }

        return [$value, $type];
    }

    /**
     * Matches columns to corresponding types
     *
     * Both $columns and $types should either be numeric based or string key based at
     * the same time.
     *
     * @param array $columns list or associative array of columns and parameters to be bound with types
     * @param array $types list or associative array of types
     * @return array
     */
    public function matchTypes(array $columns, array $types): array
    {
        if (!is_int(key($types))) {
            $positions = array_intersect_key(array_flip($columns), $types);
            $types = array_intersect_key($types, $positions);
            $types = array_combine($positions, $types);
        }

        return $types;
    }
}
