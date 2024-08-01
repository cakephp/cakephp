<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

/**
 * Value object for testing mappings.
 */
class UuidValue
{
    public $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}
