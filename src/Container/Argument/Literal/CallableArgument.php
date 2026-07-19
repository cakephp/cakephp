<?php
declare(strict_types=1);

namespace Cake\Container\Argument\Literal;

use Cake\Container\Argument\LiteralArgument;

class CallableArgument extends LiteralArgument
{
    /**
     * @param callable $value
     */
    public function __construct(callable $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_CALLABLE);
    }
}
