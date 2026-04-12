<?php
declare(strict_types=1);

namespace Cake\Container\Argument\Literal;

use Cake\Container\Argument\LiteralArgument;

class IntegerArgument extends LiteralArgument
{
    /**
     * @param int $value
     */
    public function __construct(int $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_INT);
    }
}
