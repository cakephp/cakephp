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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

class HashValidator
{
    protected array $schema;

    /**
     * @param array $schema Hash schema
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema + ['allowPaths' => true, 'strict' => true, 'required' => false, 'refs' => []];
    }

    /**
     * Validates hash against schema.
     *
     * @param array $hash Hash to validate
     * @param array $existing Existing hash that $hash would update.
     * @return array
     */
    public function validate(array $hash, array $existing = []): array
    {
        if ($this->schema['allowPaths']) {
            $hash = Hash::expand($hash);
        }

         return $this->validateShape(['fields' => $this->schema['fields']], $hash, $existing, '');
    }

    /**
     * Validate an array shape.
     *
     * @param array $shapeSpec Shape specification
     * @param array $hash Hash to validate
     * @param array $existing Existing hash that $hash would update.
     * @param string $path Shape path
     * @return array
     */
    protected function validateShape(array $shapeSpec, array $hash, array $existing, string $path): array
    {
        $errors = [];
        foreach ($hash as $field => $value) {
            $fieldPath = $this->appendFieldToPath($path, $field);

            $fieldSpec = $this->getFieldSpec($shapeSpec['fields'], $field);
            if ($fieldSpec === null) {
                if ($shapeSpec['strict'] ?? $this->schema['strict']) {
                    $errors[$fieldPath] = 'Field does not exist in schema.';
                }
                continue;
            }

            $errors += static::validateField($fieldSpec, $value, $existing[$field] ?? [], $fieldPath);
        }

        $missing = array_diff(array_keys($shapeSpec['fields']), array_keys($hash));
        foreach ($missing as $field) {
            $fieldSpec = static::getFieldSpec($shapeSpec['fields'], $field);
            if (
                !array_key_exists($field, $existing) &&
                ($fieldSpec['required'] ?? ($shapeSpec['required'] ?? $this->schema['required']))
            ) {
                $errors[$this->appendFieldToPath($path, $field)] = 'Required field missing from hash.';
            }
        }

        return $errors;
    }

    /**
     * Validates an array shape field.
     *
     * @param array $fieldSpec Field specification
     * @param mixed $value Field value
     * @param array $existing Existing hash that $value would update.
     * @param string $path Field path
     * @return array
     */
    protected function validateField(array $fieldSpec, $value, array $existing, string $path): array
    {
        foreach ((array)$fieldSpec['type'] as $typeId => $typeValue) {
            if ($typeId === 'array{}') {
                if (is_array($value)) {
                    $ref = $typeValue['ref'] ?? null;
                    if ($ref) {
                        $typeValue = $this->schema['refs'][$ref];
                    }

                    return static::validateShape($typeValue, $value, $existing, $path);
                }
                continue;
            }

            if ($this->isUnionType((array)$fieldSpec['type'], $value)) {
                return [];
            }
        }

        return [$path => 'Field value does not match expected type.'];
    }

    /**
     * Appends key to dot notation path.
     *
     * @param string $path Field path
     * @param string $field Field name
     * @return string
     */
    protected function appendFieldToPath(string $path, string $field): string
    {
        if ($path === '') {
            return $field;
        }

        return $path . '.' . $field;
    }

    /**
     * Gets normalized field specification or null if not found.
     *
     * @param array $specs Field specifications
     * @param string $field Field name
     * @return array|null
     */
    protected function getFieldSpec(array $specs, string $field): ?array
    {
        $spec = $specs[$field] ?? null;
        if ($spec === null) {
            return null;
        }

        if (is_string($spec)) {
            return ['type' => (array)$spec];
        }

        return $spec;
    }

    /**
     * Checks if $value maches expected $type.
     *
     * @param string $type Type name
     * @param mixed $value Value to check
     * @return bool
     */
    protected function isType(string $type, $value): bool
    {
        switch ($type) {
            case 'array':
                return is_array($value);
            case 'list':
                if (!is_array($value)) {
                    return false;
                }

                $e = 0;
                foreach ($value as $i => $v) {
                    if ($i !== $e++) {
                        return false;
                    }
                }

                return true;
            case 'string':
                return is_string($value);
            case 'float':
                return is_float($value);
            case 'int':
                return is_int($value);
            case 'bool':
                return is_bool($value);
            case 'null':
                return $value === null;
            case 'mixed':
                return true;
        }

        return $value instanceof $type;
    }

    /**
     * Checks if a value matches expected generic type.
     *
     * @param string $typeId Type ID
     * @param array|string $typeSpec Type specification
     * @param mixed $value Value to check
     * @return bool
     */
    protected function isGenericType(string $typeId, $typeSpec, $value): bool
    {
        switch ($typeId) {
            case 'array<>':
                if (!is_array($value)) {
                    return false;
                }

                foreach ($value as $i => $v) {
                    if (!$this->isUnionType((array)$typeSpec, $v)) {
                        return false;
                    }
                }

                return true;
            case 'list<>':
                if (!is_array($value)) {
                    return false;
                }

                $e = 0;
                foreach ($value as $i => $v) {
                    if ($i !== $e++) {
                        return false;
                    }

                    if (!$this->isUnionType((array)$typeSpec, $v)) {
                        return false;
                    }
                }

                return true;
            case 'class-string<>':
                foreach ((array)$typeSpec as $string) {
                    /** @var class-string $string */
                    if (is_a($value, $string, true)) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }

    /**
     * Checks if a value is one of the types in a union type.
     *
     * Does not check for array shape type: 'array{}'.
     *
     * @param array $union Type union
     * @param mixed $value Value to check
     * @return bool
     */
    protected function isUnionType(array $union, $value): bool
    {
        $isType = false;
        foreach ($union as $typeId => $typeSpec) {
            if (is_string($typeId)) {
                if ($this->isGenericType($typeId, $typeSpec, $value)) {
                    $isType = true;
                    break;
                }
            } elseif ($this->isType($typeSpec, $value)) {
                $isType = true;
                break;
            }
        }

        return $isType;
    }
}
