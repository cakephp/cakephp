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

use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Container ServiceProvider
 *
 * Service provider bundle related services together helping
 * to organize your application's dependencies. They also help
 * improve performance of applications with many services by
 * allowing service registration to be deferred until services are needed.
 */
abstract class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * List of ids of services this provider provides.
     *
     * @var array<string>
     * @see ServiceProvider::provides()
     */
    protected array $provides = [];

    /**
     * Get the container.
     *
     * @return \Cake\Core\ContainerInterface
     */
    public function getContainer(): DefinitionContainerInterface
    {
        $container = parent::getContainer();

        assert(
            $container instanceof ContainerInterface,
            sprintf(
                'Unexpected container type. Expected `%s` got `%s` instead.',
                ContainerInterface::class,
                get_debug_type($container)
            )
        );

        return $container;
    }

    /**
     * Delegate to the bootstrap() method
     *
     * This method wraps the league/container function so users
     * only need to use the CakePHP bootstrap() interface.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootstrap($this->getContainer());
    }

    /**
     * Bootstrap hook for ServiceProviders
     *
     * This hook should be implemented if your service provider
     * needs to register additional service providers, load configuration
     * files or do any other work when the service provider is added to the
     * container.
     *
     * @param \Cake\Core\ContainerInterface $container The container to add services to.
     * @return void
     */
    public function bootstrap(ContainerInterface $container): void
    {
    }

    /**
     * Call the abstract services() method.
     *
     * This method primarily exists as a shim between the interface
     * that league/container has and the one we want to offer in CakePHP.
     *
     * @return void
     */
    public function register(): void
    {
        $this->services($this->getContainer());
    }

    /**
     * The provides method is a way to let the container know that a service
     * is provided by this service provider.
     *
     * Every service that is registered via this service provider must have an
     * alias added to this array or it will be ignored.
     *
     * @param string $id Identifier.
     * @return bool
     */
    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }

    /**
     * Register the services in a provider.
     *
     * All services registered in this method should also be included in the $provides
     * property so that services can be located.
     *
     * @param \Cake\Core\ContainerInterface $container The container to add services to.
     * @return void
     */
    abstract public function services(ContainerInterface $container): void;
}
