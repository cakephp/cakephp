<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;

/**
 * This is a factory for creating Command instances.
 *
 * This factory can be replaced or extended if you need to customize building
 * your command objects.
 */
class CommandFactory implements CommandFactoryInterface
{
    /**
     * Constructor
     *
     * @param \Cake\Container\ContainerInterface|null $container The container to use if available.
     */
    public function __construct(protected ?ContainerInterface $container = null) {}

    /**
     * @inheritDoc
     */
    public function create(string $className): CommandInterface
    {
        // `has()` also returns true for any existing class due to auto-wiring,
        // so `hasDefinition()` is used here to check for an explicit registration only.
        if ($this->container?->hasDefinition($className)) {
            return $this->container->get($className);
        }

        if ($this->container && $this->hasConstructorDependencies($className)) {
            if (!$this->container->hasDefinition(CommandFactoryInterface::class)) {
                $this->container->add(CommandFactoryInterface::class, $this);
            }

            /** @var \Cake\Console\CommandInterface */
            return $this->container->getNew($className);
        }

        /** @var \Cake\Console\CommandInterface */
        return new $className($this);
    }

    /**
     * Check whether a command needs other constructor dependencies than this factory.
     *
     * Those commands are auto-wired by the container.
     *
     * @param class-string<\Cake\Console\CommandInterface> $className Command class name.
     * @return bool
     */
    protected function hasConstructorDependencies(string $className): bool
    {
        $constructor = new ReflectionClass($className)->getConstructor();
        if ($constructor === null) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || !is_a($this, $type->getName())) {
                return true;
            }
        }

        return false;
    }
}
