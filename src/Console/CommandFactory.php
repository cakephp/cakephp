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
    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

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

        /** @var \Cake\Console\CommandInterface */
        return new $className($this);
    }
}
