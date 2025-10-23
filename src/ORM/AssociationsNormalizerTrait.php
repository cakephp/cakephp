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
namespace Cake\ORM;

/**
 * Contains methods for parsing the associated tables array that is typically
 * passed to a save operation
 */
trait AssociationsNormalizerTrait
{
    /**
     * Returns an array out of the original passed associations list where dot notation
     * is transformed into nested arrays so that they can be parsed by other routines.
     *
     * This method now supports the same nested array format as contain(), allowing:
     * - Dot notation: ['First.Second']
     * - Nested arrays: ['First' => ['Second', 'Third']]
     * - Mixed with options: ['First' => ['Second', 'onlyIds' => true]]
     *
     * @param array|string $associations The array of included associations.
     * @return array An array having dot notation transformed into nested arrays
     */
    protected function normalizeAssociations(array|string $associations): array
    {
        $result = [];
        foreach ((array)$associations as $table => $options) {
            $pointer = &$result;

            if (is_int($table)) {
                $table = $options;
                $options = [];
            }

            // Handle nested array format like contain()
            // Only transform if the array looks like it contains associations (not just a simple array value)
            if (is_array($options) && !isset($options['associated']) && $this->shouldExtractAssociations($options)) {
                [$nestedAssociations, $actualOptions] = $this->extractAssociations($options);
                if ($nestedAssociations) {
                    $actualOptions['associated'] = $this->normalizeAssociations($nestedAssociations);
                }
                $options = $actualOptions;
            }

            if (!str_contains($table, '.')) {
                $result[$table] = $options;
                continue;
            }

            $path = explode('.', $table);
            $table = array_pop($path);
            $first = array_shift($path);
            assert(is_string($first));

            $pointer += [$first => []];
            $pointer = &$pointer[$first];
            $pointer += ['associated' => []];

            foreach ($path as $t) {
                $pointer += ['associated' => []];
                $pointer['associated'] += [$t => []];
                $pointer['associated'][$t] += ['associated' => []];
                $pointer = &$pointer['associated'][$t];
            }

            $pointer['associated'] += [$table => []];
            $pointer['associated'][$table] = $options + $pointer['associated'][$table];
        }

        return $result['associated'] ?? $result;
    }

    /**
     * Returns the list of known option keys that should not be treated as associations.
     *
     * @return array<string>
     */
    protected function getKnownOptions(): array
    {
        return [
            'onlyIds',
            'validate',
            'fields',
            'patchableFields',
            'forceNew',
            'strictFields',
            'queryBuilder',
            'finder',
            'foreignKey',
            'joinType',
            'propertyName',
            'strategy',
            'negateMatch',
            'conditions',
            'isMerge',
            'junctionProperty',
        ];
    }

    /**
     * Determines if an array should have associations extracted from it.
     *
     * Returns true if the array appears to be mixing association names with options,
     * or if it contains nested association structures (like contain() format).
     * Returns false for simple arrays that should be kept as-is.
     *
     * @param array $options The options array to check.
     * @return bool
     */
    protected function shouldExtractAssociations(array $options): bool
    {
        // Empty arrays should not be transformed
        if (!$options) {
            return false;
        }

        $knownOptions = $this->getKnownOptions();

        $hasKnownOption = false;
        $hasStringKeys = false;
        $hasNestedArrayValues = false;
        $hasMultipleItems = count($options) > 1;

        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $hasStringKeys = true;
                if (in_array($key, $knownOptions, true)) {
                    $hasKnownOption = true;
                }
            }
            // Check if value is an array (potential nested association)
            if (is_array($value)) {
                $hasNestedArrayValues = true;
            }
        }

        // Only extract associations if:
        // 1. We have a known option key (mixing associations and options)
        // 2. We have string keys AND nested array values (contain-like format with nested associations)
        // 3. We have multiple items (likely a list of associations like ['Users', 'Comments'])
        return $hasKnownOption || ($hasStringKeys && $hasNestedArrayValues) || $hasMultipleItems;
    }

    /**
     * Extracts association names from options array, separating them from actual options.
     *
     * This allows the same nested array format as contain():
     * - ['Users', 'Comments'] → associations
     * - ['Users' => [...], 'Comments'] → associations
     * - ['onlyIds' => true, 'validate' => false] → options only
     * - ['Users', 'onlyIds' => true] → mixed
     *
     * @param array $options The options array that may contain nested associations.
     * @return array An array with two elements: [associations, options]
     */
    protected function extractAssociations(array $options): array
    {
        $associations = [];
        $actualOptions = [];
        $knownOptions = $this->getKnownOptions();

        foreach ($options as $key => $value) {
            // Numeric keys are always association names
            if (is_int($key)) {
                $associations[] = $value;
                continue;
            }

            // Known option keys
            if (in_array($key, $knownOptions, true)) {
                $actualOptions[$key] = $value;
                continue;
            }

            // Everything else is treated as an association name
            // This matches contain() behavior and includes special keys like _joinData
            $associations[$key] = $value;
        }

        return [$associations, $actualOptions];
    }
}
