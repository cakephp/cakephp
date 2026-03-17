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
    public function addArgument(mixed $arg, ?string $name = null): self;

    /**
     * @param array $args
     * @return $this
     */
    public function addArguments(array $args): self;

    /**
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function addMethodCall(string $method, array $args = []): self;

    /**
     * @param array $methods
     * @return $this
     */
    public function addMethodCalls(array $methods = []): self;

    /**
     * @param string $tag
     * @return $this
     */
    public function addTag(string $tag): self;

    /**
     * @return string
     */
    public function getAlias(): string;

    /**
     * @return mixed
     */
    public function getConcrete(): mixed;

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
    public function setAlias(string $id): self;

    /**
     * @param mixed $concrete
     * @return $this
     */
    public function setConcrete(mixed $concrete): self;

    /**
     * @param bool $shared
     * @return $this
     */
    public function setShared(bool $shared): self;
}
