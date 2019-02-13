<?php
declare(strict_types=1);
namespace TestApp\Log\Object;

/**
 * used for testing when an object is passed to a logger
 */
class StringObject
{
    /**
     * String representation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return 'Hey!';
    }
}
