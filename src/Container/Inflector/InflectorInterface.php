<?php
declare(strict_types=1);

namespace Cake\Container\Inflector;

interface InflectorInterface
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param object $object
     * @return void
     */
    public function inflect(object $object): void;

    /**
     * @param string $name
     * @param array $args
     * @return $this
     */
    public function invokeMethod(string $name, array $args): InflectorInterface;

    /**
     * @param array $methods
     * @return $this
     */
    public function invokeMethods(array $methods): InflectorInterface;

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties): InflectorInterface;

    /**
     * @param string $property
     * @param mixed $value
     * @return $this
     */
    public function setProperty(string $property, mixed $value): InflectorInterface;
}
