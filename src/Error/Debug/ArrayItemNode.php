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
namespace Cake\Error\Debug;

/**
 * Dump node for Array Items.
 */
class ArrayItemNode implements NodeInterface
{
    /**
     * @var \Cake\Error\Debug\NodeInterface
     */
    private $key;

    /**
     * @var \Cake\Error\Debug\NodeInterface
     */
    private $value;

    /**
     * Constructor
     *
     * @param \Cake\Error\Debug\NodeInterface $key The node for the item key
     * @param \Cake\Error\Debug\NodeInterface $value The node for the array value
     */
    public function __construct(NodeInterface $key, NodeInterface $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Get the value
     *
     * @return \Cake\Error\Debug\NodeInterface
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the key
     *
     * @return \Cake\Error\Debug\NodeInterface
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return [$this->value];
    }
}
