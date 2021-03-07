<?php
declare(strict_types=1);

namespace TestApp\Database;

class SchemaAwareTypeValueObject
{
    protected $_value;

    public function __construct(string $value)
    {
        $this->_value = $value;
    }

    public function value()
    {
        return $this->_value;
    }
}
