<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

class DefaultValueArgument extends ResolvableArgument implements DefaultValueInterface
{
    /**
     * @param string $value
     * @param mixed|null $defaultValue
     */
    public function __construct(string $value, protected mixed $defaultValue = null)
    {
        parent::__construct($value);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }
}
