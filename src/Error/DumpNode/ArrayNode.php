<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\DumpNode;

/**
 * Dump node for Array values.
 */
class ArrayNode implements NodeInterface
{
    /**
     * @var \Cake\Error\DumpNode\ItemNode[]
     */
    private $items;

    /**
     * Constructor
     *
     * @param \Cake\Error\DumpNode\ItemNode[] $items The items for the array
     */
    public function __construct(array $items = [])
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Add an item
     *
     * @param \Cake\Error\DumpNode\ItemNode
     * @return void
     */
    public function add(ItemNode $node): void
    {
        $this->items[] = $node;
    }

    /**
     * Get the contained items
     *
     * @return \Cake\Error\DumpNode\ItemNode[]
     */
    public function getValue(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return $this->items;
    }
}
