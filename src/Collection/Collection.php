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

use ArrayIterator;
use Exception;
use IteratorIterator;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 *
 * @template TKey
 * @template TValue
 * @extends \IteratorIterator<TKey, TValue, \Traversable<TKey, TValue>>
 * @implements \Cake\Collection\CollectionInterface<TKey, TValue>
 */
class Collection extends IteratorIterator implements CollectionInterface
{
    use CollectionTrait;

    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param iterable $items Items.
     * @throws \InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct(iterable $items)
    {
        if (is_array($items)) {
            $items = new ArrayIterator($items);
        }

        parent::__construct($items);
    }

    /**
     * Returns an array for serializing this of this object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->buffered()->toArray();
    }

    /**
     * Rebuilds the Collection instance.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->__construct($data);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        try {
            $count = $this->count();
        } catch (Exception) {
            $count = 'An exception occurred while getting count';
        }

        return [
            'count' => $count,
        ];
    }
}
