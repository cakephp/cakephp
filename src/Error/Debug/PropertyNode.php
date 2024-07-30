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
 * Dump node for object properties.
 */
class PropertyNode implements NodeInterface
{
    /**
     * Constructor
     *
     * @param string $name The property name
     * @param string|null $visibility The visibility of the property.
     * @param \Cake\Error\Debug\NodeInterface $value The property value node.
     */
    public function __construct(private readonly string $name, private readonly ?string $visibility, private readonly NodeInterface $value)
    {
    }

    /**
     * Get the value
     */
    public function getValue(): NodeInterface
    {
        return $this->value;
    }

    /**
     * Get the property visibility
     */
    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    /**
     * Get the property name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return [$this->value];
    }
}
