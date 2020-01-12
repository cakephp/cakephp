<?php
declare(strict_types=1);

namespace Cake\Error\DumpNode;

interface NodeInterface
{
    public function getChildren(): array;

    public function getValue();
}
