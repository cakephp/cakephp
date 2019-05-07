<?php
declare(strict_types=1);

namespace TestApp\Collection;

use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;

class TestCollection extends \IteratorIterator implements CollectionInterface
{
    use CollectionTrait;

    /**
     * @param iterable $items
     * @throws \InvalidArgumentException
     */
    public function __construct(iterable $items)
    {
        if (is_array($items)) {
            $items = new \ArrayIterator($items);
        }

        if (!($items instanceof \Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new \InvalidArgumentException($msg);
        }

        parent::__construct($items);
    }
}
