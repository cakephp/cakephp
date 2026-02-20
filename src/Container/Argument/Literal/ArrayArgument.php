<?php
declare(strict_types=1);

namespace Cake\Container\Argument\Literal;

use Cake\Container\Argument\LiteralArgument;

class ArrayArgument extends LiteralArgument
{
    /**
     * @param array $value
     */
    public function __construct(array $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_ARRAY);
    }
}
