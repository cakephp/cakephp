<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ArrayNode implements NodeInterface
{
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(ItemNode $node)
    {
        $this->items[] = $node;

        return $this;
    }

    public function getValue()
    {
        return $this->items;
    }

    public function getChildren(): array
    {
        return $this->items;
    }
}
