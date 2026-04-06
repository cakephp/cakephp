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

use Cake\Container\Definition\DefinitionInterface as CakeDefinitionInterface;
use League\Container\ContainerAwareInterface;
use League\Container\Definition\DefinitionInterface;
use League\Container\DefinitionContainerInterface;

/**
 * Bridge for Definition objects.
 *
 * Wraps CakePHP's Definition to satisfy League's DefinitionInterface.
 *
 * @internal
 */
class CakeDefinitionBridge implements DefinitionInterface
{
    /**
     * @param \Cake\Container\Definition\DefinitionInterface $definition The wrapped definition
     * @param \League\Container\DefinitionContainerInterface $container The container
     */
    public function __construct(
        protected CakeDefinitionInterface $definition,
        protected DefinitionContainerInterface $container,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function addArgument(mixed $arg): DefinitionInterface
    {
        $this->definition->addArgument($arg);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addArguments(array $args): DefinitionInterface
    {
        $this->definition->addArguments($args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCall(string $method, array $args = []): DefinitionInterface
    {
        $this->definition->addMethodCall($method, $args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCalls(array $methods = []): DefinitionInterface
    {
        $this->definition->addMethodCalls($methods);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addTag(string $tag): DefinitionInterface
    {
        $this->definition->addTag($tag);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return $this->definition->getAlias();
    }

    /**
     * @inheritDoc
     */
    public function getConcrete(): mixed
    {
        return $this->definition->getConcrete();
    }

    /**
     * @inheritDoc
     */
    public function getTags(): array
    {
        return $this->definition->getTags();
    }

    /**
     * @inheritDoc
     */
    public function hasTag(string $tag): bool
    {
        return $this->definition->hasTag($tag);
    }

    /**
     * @inheritDoc
     */
    public function isShared(): bool
    {
        return $this->definition->isShared();
    }

    /**
     * @inheritDoc
     */
    public function resolve(): mixed
    {
        return $this->definition->resolve();
    }

    /**
     * @inheritDoc
     */
    public function resolveNew(): mixed
    {
        return $this->definition->resolveNew();
    }

    /**
     * @inheritDoc
     */
    public function setAlias(string $id): DefinitionInterface
    {
        // CakePHP's Definition doesn't have setAlias, but we can return self
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setConcrete(mixed $concrete): DefinitionInterface
    {
        $this->definition->setConcrete($concrete);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setShared(bool $shared): DefinitionInterface
    {
        $this->definition->setShared($shared);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): DefinitionContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;

        return $this;
    }
}
