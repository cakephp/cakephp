<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ClassNode implements NodeInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Cake\Error\DumpNode\PropertyNode[]
     */
    private $properties = [];

    public function __construct(string $class, int $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    public function addProperty(PropertyNode $node)
    {
        $this->properties[] = $node;
    }

    public function getValue(): string
    {
        return $this->class;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getChildren(): array
    {
        return $this->properties;
    }
}
