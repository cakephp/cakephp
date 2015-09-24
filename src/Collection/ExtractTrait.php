<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection;

use Traversable;

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
     * @param string|callable $callback A dot separated path of column to follow
     * so that the final one can be returned or a callable that will take care
     * of doing that.
     * @return callable
     */
    protected function _propertyExtractor($callback)
    {
        if (!is_string($callback)) {
            return $callback;
        }

        $path = explode('.', $callback);

        if (strpos($callback, '{*}') !== false) {
            return function ($element) use ($path) {
                return $this->_extract($element, $path);
            };
        }

        return function ($element) use ($path) {
            return $this->_simpleExtract($element, $path);
        };
    }

    /**
     * Returns a column from $data that can be extracted
     * by iterating over the column names contained in $path.
     * It will return arrays for elements in represented with `{*}`
     *
     * @param array|\ArrayAccess $data Data.
     * @param array $path Path to extract from.
     * @return mixed
     */
    protected function _extract($data, $path)
    {
        $value = null;
        $collectionTransform = false;

        foreach ($path as $i => $column) {
            if ($column === '{*}') {
                $collectionTransform = true;
                continue;
            }

            if ($collectionTransform &&
                !($data instanceof Traversable || is_array($data))) {
                return null;
            }

            if ($collectionTransform) {
                $rest = implode('.', array_slice($path, $i));
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
     * @param array|\ArrayAccess $data Data.
     * @param array $path Path to extract from.
     * @return mixed
     */
    protected function _simpleExtract($data, $path)
    {
        $value = null;
        foreach ($path as $column) {
            if (!isset($data[$column])) {
                return null;
            }
            $value = $data[$column];
            $data = $value;
        }
        return $value;
    }

    /**
     * Returns a callable that receives a value and will return whether or not
     * it matches certain condition.
     *
     * @param array $conditions A key-value list of conditions to match where the
     * key is the property path to get from the current item and the value is the
     * value to be compared the item with.
     * @return callable
     */
    protected function _createMatcherFilter(array $conditions)
    {
        $matchers = [];
        foreach ($conditions as $property => $value) {
            $extractor = $this->_propertyExtractor($property);
            $matchers[] = function ($v) use ($extractor, $value) {
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
