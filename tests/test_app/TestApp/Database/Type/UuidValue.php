<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

/**
 * Value object for testing mappings.
 */
class UuidValue
{
    public function __construct(public mixed $value)
    {
    }
}
