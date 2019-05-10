<?php
declare(strict_types=1);

namespace TestApp\Database\Type;

use Cake\Database\Type\IntegerType;

/**
 * Mock class for testing baseType inheritance
 */
class IntType extends IntegerType
{
    public function getBaseType(): string
    {
        return 'integer';
    }
}
