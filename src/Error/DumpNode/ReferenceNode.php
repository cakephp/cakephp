<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ReferenceNode implements NodeInterface
{
    private $class;
    private $id;

    public function __construct(string $class, string $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    public function getValue(): string
    {
        return $this->class;
    }

    public function getId(): string
    {
        return $this->visibility;
    }

    public function getChildren(): array
    {
        return [];
    }
}
