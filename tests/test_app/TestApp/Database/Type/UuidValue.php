<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

/**
 * Value object for testing mappings.
 */
class UuidValue
{
    /**
     * @param mixed $value
     */
    public function __construct(public $value)
    {
    }
}
