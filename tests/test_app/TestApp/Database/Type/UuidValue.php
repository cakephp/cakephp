<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

/**
 * Value object for testing mappings.
 */
class UuidValue
{
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
