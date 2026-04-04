<?php
declare(strict_types=1);

namespace Cake\Container\Definition;

use Cake\Container\ContainerAwareInterface;

interface DefinitionInterface extends ContainerAwareInterface
{
    /**
     * @param mixed $arg
     * @param string|null $name
     * @return $this
     */
    public function addArgument(mixed $arg, ?string $name = null): DefinitionInterface;

    /**
     * @param array $args
     * @return $this
     */
    public function addArguments(array $args): DefinitionInterface;

    /**
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function addMethodCall(string $method, array $args = []): DefinitionInterface;

    /**
     * @param array $methods
     * @return $this
     */
    public function addMethodCalls(array $methods = []): DefinitionInterface;

    /**
     * @param string $tag
     * @return $this
     */
    public function addTag(string $tag): DefinitionInterface;

    /**
     * @return string
     */
    public function getAlias(): string;

    /**
     * @return mixed
     */
    public function getConcrete(): mixed;

    /**
     * @return array<string>
     */
    public function getTags(): array;

    /**
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * @return bool
     */
    public function isShared(): bool;

    /**
     * @return mixed
     */
    public function resolve(): mixed;

    /**
     * @return mixed
     */
    public function resolveNew(): mixed;

    /**
     * @param string $id
     * @return $this
     */
    public function setAlias(string $id): DefinitionInterface;

    /**
     * @param mixed $concrete
     * @return $this
     */
    public function setConcrete(mixed $concrete): DefinitionInterface;

    /**
     * @param bool $shared
     * @return $this
     */
    public function setShared(bool $shared): DefinitionInterface;
}
