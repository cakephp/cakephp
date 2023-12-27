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
namespace Cake\Collection\Iterator;

use IteratorIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use Traversable;

/**
 * An iterator that can be used to generate nested iterators out of a collection
 * of items by applying an function to each of the elements in this iterator.
 *
 * @internal
 * @see \Cake\Collection\Collection::unfold()
 * @template-extends \IteratorIterator<mixed, mixed, \Traversable<mixed, mixed>>
 */
class UnfoldIterator extends IteratorIterator
{
    /**
     * @var int
     */
    protected int $unfoldedKey = 0;

    /**
     * Creates the iterator that will generate child iterators from each of the
     * elements it was constructed with.
     *
     * @param \Traversable $items The list of values to iterate
     * @param callable $unfolder A callable function that will receive the
     * current item and key. It must return an array or Traversable object
     * out of which the nested iterators will be yielded.
     */
    public function __construct(Traversable $items, callable $unfolder)
    {
        // phpcs:disable
        $itemsIterator = new
            /**
             * @template-extends \IteratorIterator<mixed, mixed, \Traversable<mixed, mixed>>
             */
            class ($items, $unfolder) extends IteratorIterator implements RecursiveIterator
            {
                /**
                 * @var callable
                 */
                protected $unfolder;

                /**
                 * Creates the iterator that will generate child iterators from each of the
                 * elements it was constructed with.
                 *
                 * @param \Traversable $items The list of values to iterate
                 * @param callable $unfolder A callable function that will receive the
                 * current item and key. It must return an array or Traversable object
                 * out of which the nested iterators will be yielded.
                 */
                public function __construct(Traversable $items, callable $unfolder)
                {
                    parent::__construct($items);
                    $this->unfolder = $unfolder;
                }

                /**
                 * @return bool
                 */
                public function hasChildren(): bool
                {
                    return true;
                }

                /**
                 * @return \RecursiveIterator
                 */
                public function getChildren(): RecursiveIterator
                {
                    return new NoChildrenIterator(($this->unfolder)($this->current(), $this->key()));
                }
            };
        // phpcs:enable

        parent::__construct(new RecursiveIteratorIterator($itemsIterator));
    }

    /**
     * See \IteratorIterator::key)
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->unfoldedKey;
    }

    /**
     * See \IteratorIterator::next)
     *
     * @return void
     */
    public function next(): void
    {
        parent::next();
        $this->unfoldedKey++;
    }

    /**
     * See \IteratorIterator::rewind()
     *
     * @return void
     */
    public function rewind(): void
    {
        parent::rewind();
        $this->unfoldedKey = 0;
    }
}
