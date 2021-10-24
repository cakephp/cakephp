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
 * @since         3.0.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use MultipleIterator;
use Serializable;

/**
 * Creates an iterator that returns elements grouped in pairs
 *
 * ### Example
 *
 * ```
 *  $iterator = new ZipIterator([[1, 2], [3, 4]]);
 *  $iterator->toList(); // Returns [[1, 3], [2, 4]]
 * ```
 *
 * You can also chose a custom function to zip the elements together, such
 * as doing a sum by index:
 *
 * ### Example
 *
 * ```
 *  $iterator = new ZipIterator([[1, 2], [3, 4]], function ($a, $b) {
 *    return $a + $b;
 *  });
 *  $iterator->toList(); // Returns [4, 6]
 * ```
 */
class ZipIterator extends MultipleIterator implements CollectionInterface, Serializable
{
    use CollectionTrait;

    /**
     * The function to use for zipping items together
     *
     * @var callable|null
     */
    protected $_callback;

    /**
     * Contains the original iterator objects that were attached
     *
     * @var array
     */
    protected $_iterators = [];

    /**
     * Creates the iterator to merge together the values by for all the passed
     * iterators by their corresponding index.
     *
     * @param array $sets The list of array or iterators to be zipped.
     * @param callable|null $callable The function to use for zipping the elements of each iterator.
     */
    public function __construct(array $sets, ?callable $callable = null)
    {
        $sets = array_map(function ($items) {
            return (new Collection($items))->unwrap();
        }, $sets);

        $this->_callback = $callable;
        parent::__construct(MultipleIterator::MIT_NEED_ALL | MultipleIterator::MIT_KEYS_NUMERIC);

        foreach ($sets as $set) {
            $this->_iterators[] = $set;
            $this->attachIterator($set);
        }
    }

    /**
     * Returns the value resulting out of zipping all the elements for all the
     * iterators with the same positional index.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if ($this->_callback === null) {
            return parent::current();
        }

        return call_user_func_array($this->_callback, parent::current());
    }

    /**
     * Returns a string representation of this object that can be used
     * to reconstruct it
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->_iterators);
    }

    /**
     * Magic method used for serializing the iterator instance.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->_iterators;
    }

    /**
     * Unserializes the passed string and rebuilds the ZipIterator instance
     *
     * @param string $iterators The serialized iterators
     * @return void
     */
    public function unserialize($iterators): void
    {
        parent::__construct(MultipleIterator::MIT_NEED_ALL | MultipleIterator::MIT_KEYS_NUMERIC);
        $this->_iterators = unserialize($iterators);
        foreach ($this->_iterators as $it) {
            $this->attachIterator($it);
        }
    }

    /**
     * Magic method used to rebuild the iterator instance.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__construct(MultipleIterator::MIT_NEED_ALL | MultipleIterator::MIT_KEYS_NUMERIC);

        $this->_iterators = $data;
        foreach ($this->_iterators as $it) {
            $this->attachIterator($it);
        }
    }
}
