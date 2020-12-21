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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface as PsrInterface;

/**
 * Interface for the Dependency Injection Container in CakePHP applications
 *
 * This interface extends the PSR-11 container interface and adds
 * methods to add services and service providers to the container.
 *
 * The methods defined in this interface use the conventions provided
 * by league/container as that is the library that CakePHP uses.
 *
 * @experimental This interface is not final and can have additional
 *   methods and parameters added in future minor releases.
 */
interface ContainerInterface extends PsrInterface
{
    /**
     * Add an item to the container.
     *
     * @param string $id The class name or name of the service being registered.
     * @param mixed $concrete Either the classname an interface or name resolves to.
     *   Can also be a constructed object, Closure, or null. When null, the `$id` parameter will
     *   be used as the concrete class name.
     * @param bool $shared Set to true to make a service shared.
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function add(string $id, $concrete = null, bool $shared = false): DefinitionInterface;

    /**
     * Add a service provider to the container
     *
     * @param \League\Container\ServiceProvider\ServiceProviderInterface $provider The service provider to add.
     * @return $this
     */
    public function addServiceProvider($provider);

    /**
     * Modify an existing definition
     *
     * @param string $id The class name or name of the service being modified.
     * @return \League\Container\Definition\DefinitionInterface
     */
    public function extend(string $id): DefinitionInterface;
}
