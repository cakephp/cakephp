<?php
declare(strict_types=1);

namespace TestApp\View\Object;

use Stringable;

/**
 * TestObjectWithToString
 *
 * An object with the magic method __toString() for testing with view blocks.
 */
class TestObjectWithToString implements Stringable
{
    /**
     * Return string value.
     */
    public function __toString(): string
    {
        return "I'm ObjectWithToString";
    }
}
