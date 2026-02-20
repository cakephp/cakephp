<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

class DefaultValueArgument extends ResolvableArgument implements DefaultValueInterface
{
    protected mixed $defaultValue;

    /**
     * @param string $value
     * @param mixed|null $defaultValue
     */
    public function __construct(string $value, mixed $defaultValue = null)
    {
        $this->defaultValue = $defaultValue;
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
