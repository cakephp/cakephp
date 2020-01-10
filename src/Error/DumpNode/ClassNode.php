<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ClassNode implements NodeInterface
{
    private $class;
    private $id;
    private $properties = [];

    public function __construct(string $class, string $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    public function addProperty(PropertyNode $node)
    {
        $this->properties[] = $node;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getChildren(): array
    {
        return $this->properties;
    }
}
