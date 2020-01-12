<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

class SpecialNode implements NodeInterface
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getChildren(): array
    {
        return [];
    }
}
