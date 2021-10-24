<?php
declare(strict_types=1);

namespace TestApp\Database;

class ColumnSchemaAwareTypeValueObject
{
    protected $_value;

    public function __construct(string $value)
    {
        $this->_value = $value;
    }

    public function value(): string
    {
        return $this->_value;
    }
}
