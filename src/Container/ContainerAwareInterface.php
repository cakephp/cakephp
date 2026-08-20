<?php
declare(strict_types=1);

namespace Cake\Container;

interface ContainerAwareInterface
{
    /**
     * @return \Cake\Container\ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @param \Cake\Container\ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): self;
}
