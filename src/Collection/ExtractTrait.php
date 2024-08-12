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
namespace Cake\Collection;

use ArrayAccess;
use Closure;

/**
 * Provides utility protected methods for extracting a property or column
 * from an array or object.
 */
trait ExtractTrait
{
    /**
     * Returns a callable that can be used to extract a property or column from
     * an array or object based on a dot separated path.
     *
     * @param callable|string $path A dot separated path of column to follow
     * so that the final one can be returned or a callable that will take care
     * of doing that.
     * @return \Closure
     */
    protected function _propertyExtractor(callable|string $path): Closure
    {
        if (!is_string($path)) {
            return $path(...);
        }

        $parts = explode('.', $path);

        if (str_contains($path, '{*}')) {
            return fn ($element) => $this->_extract($element, $parts);
        }

        return function ($element) use ($parts) {
            if (!is_array($element) && !$element instanceof ArrayAccess) {
                return null;
            }

            return $this->_simpleExtract($element, $parts);
        };
    }

    /**
     * Returns a column from $data that can be extracted
     * by iterating over the column names contained in $path.
     * It will return arrays for elements in represented with `{*}`
     *
     * @param \ArrayAccess<string|int, mixed>|array $data Data.
     * @param list<string> $parts Path to extract from.
     * @return mixed
     */
    protected function _extract(ArrayAccess|array $data, array $parts): mixed
    {
        $value = null;
        $collectionTransform = false;

        foreach ($parts as $i => $column) {
            if ($column === '{*}') {
                $collectionTransform = true;
                continue;
            }

            if (
                $collectionTransform &&
                !is_iterable($data)
            ) {
                return null;
            }

            if ($collectionTransform) {
                $rest = implode('.', array_slice($parts, $i));

                return (new Collection($data))->extract($rest);
            }

            if (!isset($data[$column])) {
                return null;
            }

            $value = $data[$column];
            $data = $value;
        }

        return $value;
    }

    /**
     * Returns a column from $data that can be extracted
     * by iterating over the column names contained in $path
     *
     * @param \ArrayAccess<string|int, mixed>|array $data Data.
     * @param list<string> $parts Path to extract from.
     * @return mixed
     */
    protected function _simpleExtract(ArrayAccess|array $data, array $parts): mixed
    {
        $value = null;
        foreach ($parts as $column) {
            if (!isset($data[$column])) {
                return null;
            }
            $value = $data[$column];
            $data = $value;
        }

        return $value;
    }

    /**
     * Returns a callable that receives a value and will return whether
     * it matches certain condition.
     *
     * @param array $conditions A key-value list of conditions to match where the
     * key is the property path to get from the current item and the value is the
     * value to be compared the item with.
     * @return \Closure
     */
    protected function _createMatcherFilter(array $conditions): Closure
    {
        $matchers = [];
        foreach ($conditions as $property => $value) {
            $extractor = $this->_propertyExtractor($property);
            $matchers[] = function ($v) use ($extractor, $value): bool {
                return $extractor($v) == $value;
            };
        }

        return function ($value) use ($matchers) {
            foreach ($matchers as $match) {
                if (!$match($value)) {
                    return false;
                }
            }

            return true;
        };
    }
}
