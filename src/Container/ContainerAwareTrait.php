<?php
declare(strict_types=1);

namespace Cake\Container;

use BadMethodCallException;
use Cake\Container\Exception\ContainerException;

trait ContainerAwareTrait
{
    /**
     * @var \Cake\Container\ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container): ContainerAwareInterface
    {
        $this->container = $container;

        if ($this instanceof ContainerAwareInterface) {
            return $this;
        }

        throw new BadMethodCallException(sprintf(
            'Attempt to use (%s) while not implementing (%s)',
            self::class,
            ContainerAwareInterface::class,
        ));
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ContainerInterface
    {
        if ($this->container instanceof ContainerInterface) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set.');
    }
}
