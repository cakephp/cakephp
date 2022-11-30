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

use Cake\Collection\CollectionTrait;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use SplFixedArray;

/**
 * Represents the results obtained after executing a query for a specific table
 * This object is responsible for correctly nesting result keys reported from
 * the query, casting each field to the correct type and executing the extra
 * queries required for eager loading external associations.
 *
 * @template T of \Cake\Datasource\EntityInterface|array
 * @implements \Cake\Datasource\ResultSetInterface<T>
 */
class ResultSet implements ResultSetInterface
{
    use CollectionTrait;

    /**
     * Points to the next record number that should be fetched
     *
     * @var int
     */
    protected int $_index = 0;

    /**
     * Last record fetched from the statement
     *
     * @var \Cake\Datasource\EntityInterface|array|null
     * @psalm-var T|null
     */
    protected EntityInterface|array|null $_current;

    /**
     * Holds the count of records in this result set
     *
     * @var int
     */
    protected int $_count = 0;

    /**
     * Results that have been fetched or hydrated into the results.
     *
     * @var \SplFixedArray<T>
     */
    protected SplFixedArray $_results;

    /**
     * Constructor
     *
     * @param array $results Results array.
     */
    public function __construct(array $results)
    {
        $this->__unserialize($results);
    }

    /**
     * Returns the current record in the result iterator.
     *
     * Part of Iterator interface.
     *
     * @return \Cake\Datasource\EntityInterface|array|null
     * @psalm-return T|null
     */
    public function current(): EntityInterface|array|null
    {
        return $this->_current;
    }

    /**
     * Returns the key of the current record in the iterator.
     *
     * Part of Iterator interface.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->_index;
    }

    /**
     * Advances the iterator pointer to the next record.
     *
     * Part of Iterator interface.
     *
     * @return void
     */
    public function next(): void
    {
        $this->_index++;
    }

    /**
     * Rewinds a ResultSet.
     *
     * Part of Iterator interface.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->_index = 0;
    }

    /**
     * Whether there are more results to be fetched from the iterator.
     *
     * Part of Iterator interface.
     *
     * @return bool
     */
    public function valid(): bool
    {
        if ($this->_index < $this->_count) {
            $this->_current = $this->_results[$this->_index];

            return true;
        }

        return false;
    }

    /**
     * Get the first record from a result set.
     *
     * This method will also close the underlying statement cursor.
     *
     * @return \Cake\Datasource\EntityInterface|array|null
     * @psalm-return T|null
     */
    public function first(): EntityInterface|array|null
    {
        foreach ($this as $result) {
            return $result;
        }

        return null;
    }

    /**
     * Serializes a resultset.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->_results->toArray();
    }

    /**
     * Unserializes a resultset.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->_results = SplFixedArray::fromArray($data);
        $this->_count = $this->_results->count();
    }

    /**
     * Gives the number of rows in the result set.
     *
     * Part of the Countable interface.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->_count;
    }

    /**
     * @inheritDoc
     */
    public function countKeys(): int
    {
        // This is an optimization over the implementation provided by CollectionTrait::countKeys()
        return $this->_count;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return !$this->_count;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $currentIndex = $this->_index;
        // toArray() adjusts the current index, so we have to reset it
        $items = $this->toArray();
        $this->_index = $currentIndex;

        return [
            'items' => $items,
        ];
    }
}
