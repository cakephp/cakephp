<?php
declare(strict_types=1);

namespace Cake\Container\Argument\Literal;

use Cake\Container\Argument\LiteralArgument;

class StringArgument extends LiteralArgument
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_STRING);
    }
}
