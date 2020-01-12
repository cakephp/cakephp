<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class ArrayNode implements NodeInterface
{
    /**
     * @var \Cake\Error\DumpNode\ItemNode[]
     */
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(ItemNode $node): void
    {
        $this->items[] = $node;
    }

    public function getValue(): array
    {
        return $this->items;
    }

    public function getChildren(): array
    {
        return $this->items;
    }
}
