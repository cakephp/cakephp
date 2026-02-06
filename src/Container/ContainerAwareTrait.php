<?php
declare(strict_types=1);

namespace Cake\Container;

use BadMethodCallException;
use Cake\Container\Exception\ContainerException;

trait ContainerAwareTrait
{
    /**
     * @var \Cake\Container\DefinitionContainerInterface|null
     */
    protected ?DefinitionContainerInterface $container = null;

    /**
     * @inheritDoc
     */
    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;

        if ($this instanceof ContainerAwareInterface) {
            return $this;
        }

        throw new BadMethodCallException(sprintf(
            'Attempt to use (%s) while not implementing (%s)',
            ContainerAwareTrait::class,
            ContainerAwareInterface::class,
        ));
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): DefinitionContainerInterface
    {
        if ($this->container instanceof DefinitionContainerInterface) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set.');
    }
}
