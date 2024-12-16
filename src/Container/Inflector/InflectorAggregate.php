<?php
declare(strict_types=1);

namespace Cake\Container\Inflector;

use Cake\Container\ContainerAwareTrait;
use Generator;

class InflectorAggregate implements InflectorAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var array<\Cake\Container\Inflector\Inflector>
     */
    protected array $inflectors = [];

    /**
     * @inheritDoc
     */
    public function add(string $type, ?callable $callback = null): Inflector
    {
        $inflector = new Inflector($type, $callback);
        $this->inflectors[] = $inflector;

        return $inflector;
    }

    /**
     * @inheritDoc
     */
    public function inflect($object): mixed
    {
        foreach ($this->getIterator() as $inflector) {
            $type = $inflector->getType();

            if ($object instanceof $type) {
                $inflector->setContainer($this->getContainer());
                $inflector->inflect($object);
            }
        }

        return $object;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Generator
    {
        yield from $this->inflectors;
    }
}
