<?php
declare(strict_types=1);

namespace TestApp\Collection;

use Countable;
use IteratorIterator;

class CountableIterator extends IteratorIterator implements Countable
{
    public function __construct(mixed $items)
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
