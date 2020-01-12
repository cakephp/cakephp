<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ItemNode implements NodeInterface
{
    private $key;
    private $value;

    public function __construct(NodeInterface $key, NodeInterface $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getChildren(): array
    {
        return [$this->value];
    }
}
