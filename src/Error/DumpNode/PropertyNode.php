<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class PropertyNode implements NodeInterface
{
    private $name;
    private $visibility;
    private $value;

    public function __construct(string $name, ?string $visibility, NodeInterface $value)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChildren(): array
    {
        return [$this->value];
    }
}
