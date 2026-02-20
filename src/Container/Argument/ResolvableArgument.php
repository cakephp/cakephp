<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

class ResolvableArgument implements ResolvableArgumentInterface
{
    protected string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
