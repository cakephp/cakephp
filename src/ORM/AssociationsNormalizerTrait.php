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
    protected function _normalizeAssociations(array|string $associations): array
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
            if (is_array($options) && !isset($options['associated']) && $this->_shouldExtractAssociations($options)) {
                [$nestedAssociations, $actualOptions] = $this->_extractAssociations($options);
                if ($nestedAssociations) {
                    $actualOptions['associated'] = $this->_normalizeAssociations($nestedAssociations);
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
     * Determines if an array should have associations extracted from it.
     *
     * Returns true if the array appears to be mixing association names with options,
     * or if it contains nested association structures (like contain() format).
     * Returns false for simple arrays that should be kept as-is.
     *
     * Uses CakePHP naming conventions to detect associations vs options:
     * - Association names start with uppercase (CamelCase): Users, Articles
     * - Option keys start with lowercase (camelCase): onlyIds, conditions
     * - Special data keys start with underscore: _joinData, _ids
     *
     * @param array $options The options array to check.
     * @return bool
     */
    protected function _shouldExtractAssociations(array $options): bool
    {
        // Empty arrays should not be transformed
        if (!$options) {
            return false;
        }

        $hasOptionKey = false;
        $hasStringKeys = false;
        $hasNestedArrayValues = false;
        $hasMultipleItems = count($options) > 1;

        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $hasStringKeys = true;
                // Option keys start with lowercase letter (camelCase convention)
                if (preg_match('/^[a-z]/', $key)) {
                    $hasOptionKey = true;
                }
            }
            // Check if value is an array (potential nested association)
            if (is_array($value)) {
                $hasNestedArrayValues = true;
            }
        }

        // Only extract associations if:
        // 1. We have an option key (mixing associations and options)
        // 2. We have string keys AND nested array values (contain-like format with nested associations)
        // 3. We have multiple items (likely a list of associations like ['Users', 'Comments'])
        return $hasOptionKey || ($hasStringKeys && $hasNestedArrayValues) || $hasMultipleItems;
    }

    /**
     * Extracts association names from options array, separating them from actual options.
     *
     * Uses CakePHP naming conventions to distinguish associations from options:
     * - Association names start with uppercase (CamelCase): Users, Articles
     * - Special data keys start with underscore: _joinData, _ids (treated as associations)
     * - Option keys start with lowercase (camelCase): onlyIds, conditions
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
    protected function _extractAssociations(array $options): array
    {
        $associations = [];
        $actualOptions = [];

        foreach ($options as $key => $value) {
            // Numeric keys are always association names (string values like 'Users')
            if (is_int($key)) {
                $associations[] = $value;
                continue;
            }

            // String keys starting with uppercase or underscore are associations/data keys
            // This follows CakePHP conventions: CamelCase for models, _prefix for special data
            if (preg_match('/^[A-Z_]/', $key)) {
                $associations[$key] = $value;
                continue;
            }

            // Everything else (lowercase start) is an option key
            $actualOptions[$key] = $value;
        }

        return [$associations, $actualOptions];
    }
}
