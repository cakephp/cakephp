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
 * Dump node for class references.
 *
 * To prevent cyclic references from being output multiple times
 * a reference node can be used after an object has been seen the
 * first time.
 */
class ReferenceNode implements NodeInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var int
     */
    private $id;

    /**
     * Constructor
     *
     * @param string $class The class name
     * @param int $id The id of the referenced class.
     */
    public function __construct(string $class, int $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    /**
     * Get the class name/value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->class;
    }

    /**
     * Get the reference id for this node.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return [];
    }
}
