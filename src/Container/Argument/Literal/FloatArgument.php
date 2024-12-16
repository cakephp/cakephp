<?php
declare(strict_types=1);

namespace Cake\Container\Argument\Literal;

use Cake\Container\Argument\LiteralArgument;

class FloatArgument extends LiteralArgument
{
    /**
     * @param float $value
     */
    public function __construct(float $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_FLOAT);
    }
}
