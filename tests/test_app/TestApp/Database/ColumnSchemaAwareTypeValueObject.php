<?php
declare(strict_types=1);

namespace TestApp\Database;

class ColumnSchemaAwareTypeValueObject
{
    public function __construct(protected string $_value)
    {
    }

    public function value(): string
    {
        return $this->_value;
    }
}
