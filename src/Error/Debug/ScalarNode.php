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
 * Dump node for scalar values.
 */
class ScalarNode implements NodeInterface
{
    /**
     * Constructor
     *
     * @param string $type The type of scalar value.
     * @param resource|string|float|int|bool|null $value The wrapped value.
     */
    public function __construct(private readonly string $type, private $value)
    {
    }

    /**
     * Get the type of value
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the value
     *
     * @return resource|string|float|int|bool|null
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return [];
    }
}
