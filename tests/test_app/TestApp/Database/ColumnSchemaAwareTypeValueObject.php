<?php
declare(strict_types=1);

namespace TestApp\Database;

class ColumnSchemaAwareTypeValueObject
{
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
