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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Container\Inflector\InflectorInterface as CakeInflectorInterface;
use League\Container\Inflector\InflectorInterface;

/**
 * Bridge for Inflector objects.
 *
 * Wraps CakePHP's Inflector to satisfy League's InflectorInterface.
 *
 * @internal
 */
class CakeInflectorBridge implements InflectorInterface
{
    /**
     * @param \Cake\Container\Inflector\InflectorInterface $inflector The wrapped inflector
     */
    public function __construct(
        protected CakeInflectorInterface $inflector,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->inflector->getType();
    }

    /**
     * @inheritDoc
     */
    public function invokeMethod(string $name, array $args): InflectorInterface
    {
        $this->inflector->invokeMethod($name, $args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function invokeMethods(array $methods): InflectorInterface
    {
        $this->inflector->invokeMethods($methods);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperty(string $property, mixed $value): InflectorInterface
    {
        $this->inflector->setProperty($property, $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperties(array $properties): InflectorInterface
    {
        $this->inflector->setProperties($properties);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inflect(object $object): void
    {
        $this->inflector->inflect($object);
    }
}
