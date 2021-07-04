<?php
declare(strict_types=1);

namespace TestApp\Collection;

class CountableIterator extends \IteratorIterator implements \Countable
{
    /**
     * @param mixed $items
     */
    public function __construct($items)
    {
        $f = function () use ($items) {
            foreach ($items as $e) {
                yield $e;
            }
        };
        parent::__construct($f());
    }

    public function count(): int
    {
        return 6;
    }
}
