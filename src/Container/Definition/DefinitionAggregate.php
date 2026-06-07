<?php
declare(strict_types=1);

namespace Cake\Container\Definition;

use Cake\Container\ContainerAwareTrait;
use Cake\Container\Exception\NotFoundException;
use Generator;

class DefinitionAggregate implements DefinitionAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var array<string, \Cake\Container\Definition\DefinitionInterface>
     */
    protected array $definitions = [];

    /**
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $definition) {
            if ($definition instanceof DefinitionInterface) {
                $this->definitions[$definition->getAlias()] = $definition;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function add(string $id, $definition): DefinitionInterface
    {
        if (!($definition instanceof DefinitionInterface)) {
            $definition = new Definition($id, $definition);
        }

        $definition = $definition->setAlias($id);
        $this->definitions[$definition->getAlias()] = $definition;

        return $definition;
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, $definition): DefinitionInterface
    {
        $definition = $this->add($id, $definition);

        return $definition->setShared(true);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[Definition::normaliseAlias($id)]);
    }

    /**
     * @inheritDoc
     */
    public function hasTag(string $tag): bool
    {
        foreach ($this->getIterator() as $definition) {
            if ($definition->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDefinition(string $id): DefinitionInterface
    {
        $id = Definition::normaliseAlias($id);

        if (!isset($this->definitions[$id])) {
            throw new NotFoundException(sprintf('Alias (%s) is not being handled as a definition.', $id));
        }

        return $this->definitions[$id]->setContainer($this->getContainer());
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $id): mixed
    {
        return $this->getDefinition($id)->resolve();
    }

    /**
     * @inheritDoc
     */
    public function resolveNew(string $id): mixed
    {
        return $this->getDefinition($id)->resolveNew();
    }

    /**
     * @inheritDoc
     */
    public function resolveTagged(string $tag): array
    {
        $arrayOf = [];

        foreach ($this->getIterator() as $definition) {
            if ($definition->hasTag($tag)) {
                $arrayOf[] = $definition->setContainer($this->getContainer())->resolve();
            }
        }

        return $arrayOf;
    }

    /**
     * @inheritDoc
     */
    public function resolveTaggedNew(string $tag): array
    {
        $arrayOf = [];

        foreach ($this->getIterator() as $definition) {
            if ($definition->hasTag($tag)) {
                $arrayOf[] = $definition->setContainer($this->getContainer())->resolveNew();
            }
        }

        return $arrayOf;
    }

    /**
     * @return \Generator<string, \Cake\Container\Definition\DefinitionInterface>
     */
    public function getIterator(): Generator
    {
        yield from $this->definitions;
    }
}
