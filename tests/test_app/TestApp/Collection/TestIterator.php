<?php
declare(strict_types=1);

namespace TestApp\Collection;

use ArrayIterator;
use Cake\Collection\CollectionTrait;

/**
 * Special class to test that extending \ArrayIterator works as expected
 */
class TestIterator extends ArrayIterator
{
    use CollectionTrait;

    public $data = [];

    public function __construct($data)
    {
        $this->data = $data;

        parent::__construct($data);
    }

    public function checkValues()
    {
        return true;
    }
}
