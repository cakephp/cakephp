<?php
declare(strict_types=1);

namespace Cake\Container\Definition;

use Cake\Container\ContainerAwareInterface;
use IteratorAggregate;

/**
 * @template-extends \IteratorAggregate<string, \Cake\Container\Definition\DefinitionInterface>
 */
interface DefinitionAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    /**
     * @param string $id
     * @param mixed $definition
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function add(string $id, mixed $definition): DefinitionInterface;

    /**
     * @param string $id
     * @param mixed $definition
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function addShared(string $id, mixed $definition): DefinitionInterface;

    /**
     * @param string $id
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function getDefinition(string $id): DefinitionInterface;

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * @param string $id
     * @return mixed
     */
    public function resolve(string $id): mixed;

    /**
     * @param string $id
     * @return mixed
     */
    public function resolveNew(string $id): mixed;

    /**
     * @param string $tag
     * @return array
     */
    public function resolveTagged(string $tag): array;

    /**
     * @param string $tag
     * @return array
     */
    public function resolveTaggedNew(string $tag): array;
}
