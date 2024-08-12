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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Type\BatchCastingInterface;
use Cake\Database\Type\OptionalConvertInterface;

/**
 * An invokable class to be used for processing each of the rows in a statement
 * result, so that the values are converted to the right PHP types.
 *
 * @internal
 */
class FieldTypeConverter
{
    /**
     * @var \Cake\Database\Driver
     */
    protected Driver $driver;

    /**
     * Maps type names to conversion settings.
     *
     * @var array
     */
    protected array $conversions = [];

    /**
     * Builds the type map
     *
     * @param \Cake\Database\TypeMap $typeMap Contains the types to use for converting results
     * @param \Cake\Database\Driver $driver The driver to use for the type conversion
     */
    public function __construct(TypeMap $typeMap, Driver $driver)
    {
        $this->driver = $driver;

        $types = TypeFactory::buildAll();
        foreach ($typeMap->toArray() as $field => $typeName) {
            $type = $types[$typeName] ?? null;
            if (!$type || ($type instanceof OptionalConvertInterface && !$type->requiresToPhpCast())) {
                continue;
            }

            $this->conversions[$typeName] ??= [
                'type' => $type,
                'hasBatch' => $type instanceof BatchCastingInterface,
                'fields' => [],
            ];
            $this->conversions[$typeName]['fields'][] = $field;
        }
    }

    /**
     * Converts each of the fields in the array that are present in the type map
     * using the corresponding Type class.
     *
     * @param mixed $row The array with the fields to be casted
     * @return mixed
     */
    public function __invoke(mixed $row): mixed
    {
        if (!is_array($row)) {
            return $row;
        }

        foreach ($this->conversions as $conversion) {
            /** @var \Cake\Database\TypeInterface $type */
            $type = $conversion['type'];
            if ($conversion['hasBatch']) {
                /** @var \Cake\Database\Type\BatchCastingInterface $type */
                $row = $type->manyToPHP($row, $conversion['fields'], $this->driver);
                continue;
            }

            foreach ($conversion['fields'] as $field) {
                $row[$field] = $type->toPHP($row[$field], $this->driver);
            }
        }

        return $row;
    }
}
