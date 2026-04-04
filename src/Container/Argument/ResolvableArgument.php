<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

class ResolvableArgument implements ResolvableArgumentInterface
{
    /**
     * @param string $value
     */
    public function __construct(protected string $value)
    {
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
