<?php
declare(strict_types=1);

namespace TestApp\Collection;

use ArrayIterator;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use IteratorIterator;

class TestCollection extends IteratorIterator implements CollectionInterface
{
    use CollectionTrait;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(iterable $items)
    {
        if (is_array($items)) {
            $items = new ArrayIterator($items);
        }

        parent::__construct($items);
    }
}
