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
namespace Cake\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\I18n\Number;
use PDO;

/**
 * Float type converter.
 *
 * Use to convert float/decimal data between PHP and the database types.
 */
class FloatType extends BaseType implements BatchCastingInterface
{
    /**
     * The class to use for representing number objects
     *
     * @var string
     */
    public static string $numberClass = Number::class;

    /**
     * Whether numbers should be parsed using a locale aware parser
     * when marshalling string inputs.
     *
     * @var bool
     */
    protected bool $_useLocaleParser = false;

    /**
     * Convert integer data into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return float|null
     */
    public function toDatabase(mixed $value, Driver $driver): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float)$value;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return float|null
     * @throws \Cake\Core\Exception\CakeException
     */
    public function toPHP(mixed $value, Driver $driver): ?float
    {
        if ($value === null) {
            return null;
        }

        return (float)$value;
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, Driver $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $values[$field] = (float)$values[$field];
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into PHP floats.
     *
     * @param mixed $value The value to convert.
     * @return string|float|null Converted value.
     */
    public function marshal(mixed $value): string|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && $this->_useLocaleParser) {
            return $this->_parseValue($value);
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value) && preg_match('/^[0-9,. ]+$/', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * Sets whether to parse numbers passed to the marshal() function
     * by using a locale aware parser.
     *
     * @param bool $enable Whether to enable
     * @return $this
     */
    public function useLocaleParser(bool $enable = true)
    {
        if ($enable === false) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        if (
            static::$numberClass === Number::class ||
            is_subclass_of(static::$numberClass, Number::class)
        ) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        throw new DatabaseException(
            sprintf('Cannot use locale parsing with the %s class', static::$numberClass)
        );
    }

    /**
     * Converts a string into a float point after parsing it using the locale
     * aware parser.
     *
     * @param string $value The value to parse and convert to an float.
     * @return float
     */
    protected function _parseValue(string $value): float
    {
        $class = static::$numberClass;

        return $class::parseFloat($value);
    }
}
