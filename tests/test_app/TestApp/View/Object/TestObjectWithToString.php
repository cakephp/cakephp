<?php
declare(strict_types=1);

namespace TestApp\View\Object;

/**
 * TestObjectWithToString
 *
 * An object with the magic method __toString() for testing with view blocks.
 */
class TestObjectWithToString
{
    /**
     * Return string value.
     *
     * @return string
     */
    public function __toString()
    {
        return "I'm ObjectWithToString";
    }
}
