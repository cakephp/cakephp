<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ScalarNode implements NodeInterface
{
    private $type;
    private $value;

    public function __construct(string $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getChildren(): array
    {
        return [];
    }
}
