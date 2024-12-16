<?php
declare(strict_types=1);

namespace Cake\Container;

interface ContainerAwareInterface
{
    /**
     * @return \Cake\Container\DefinitionContainerInterface
     */
    public function getContainer(): DefinitionContainerInterface;

    /**
     * @param \Cake\Container\DefinitionContainerInterface $container
     * @return $this
     */
    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface;
}
