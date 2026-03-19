<?php
declare(strict_types=1);

namespace Cake\Core;

use Cake\Container\ContainerAwareInterface as CakeContainerAwareInterface;
use Cake\Container\Definition\DefinitionInterface as CakeDefinitionInterface;
use Cake\Container\DefinitionContainerInterface as CakeDefinitionContainerInterface;
use InvalidArgumentException;
use League\Container\ContainerAwareInterface as LeagueContainerAwareInterface;
use League\Container\Definition\DefinitionInterface as LeagueDefinitionInterface;
use League\Container\DefinitionContainerInterface as LeagueDefinitionContainerInterface;

class ContainerDefinitionAdapter implements CakeDefinitionInterface, LeagueDefinitionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(protected CakeDefinitionInterface $definition)
    {
    }

    /**
     * @inheritDoc
     */
    public function addArgument(mixed $arg, ?string $name = null): static
    {
        $this->definition->addArgument($arg, $name);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addArguments(array $args): static
    {
        $this->definition->addArguments($args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCall(string $method, array $args = []): static
    {
        $this->definition->addMethodCall($method, $args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCalls(array $methods = []): static
    {
        $this->definition->addMethodCalls($methods);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addTag(string $tag): static
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
    public function getContainer(): CakeDefinitionContainerInterface&LeagueDefinitionContainerInterface
    {
        $container = $this->definition->getContainer();

        assert($container instanceof LeagueDefinitionContainerInterface);

        return $container;
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
    public function setAlias(string $id): static
    {
        $this->definition->setAlias($id);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setConcrete(mixed $concrete): static
    {
        $this->definition->setConcrete($concrete);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(mixed $container): CakeContainerAwareInterface&LeagueContainerAwareInterface
    {
        if (!$container instanceof CakeDefinitionContainerInterface) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected container type. Expected `%s` got `%s` instead.',
                CakeDefinitionContainerInterface::class,
                get_debug_type($container),
            ));
        }

        $this->definition->setContainer($container);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setShared(bool $shared): static
    {
        $this->definition->setShared($shared);

        return $this;
    }
}
