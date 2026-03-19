<?php
declare(strict_types=1);

namespace Cake\Core;

use Cake\Container\ContainerAwareInterface as CakeContainerAwareInterface;
use Cake\Container\DefinitionContainerInterface as CakeDefinitionContainerInterface;
use Cake\Container\ServiceProvider\BootableServiceProviderInterface as CakeBootableServiceProviderInterface;
use Cake\Container\ServiceProvider\ServiceProviderInterface as CakeServiceProviderInterface;
use InvalidArgumentException;
use League\Container\DefinitionContainerInterface as LeagueDefinitionContainerInterface;
use League\Container\ServiceProvider\BootableServiceProviderInterface as LeagueBootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderInterface as LeagueServiceProviderInterface;

class ContainerServiceProviderAdapter implements CakeServiceProviderInterface, CakeBootableServiceProviderInterface
{
    /**
     * @param \League\Container\ServiceProvider\ServiceProviderInterface $provider
     * @param \Cake\Container\DefinitionContainerInterface&\Cake\Core\ContainerInterface $container
     */
    public function __construct(
        protected LeagueServiceProviderInterface $provider,
        protected CakeDefinitionContainerInterface&ContainerInterface $container,
    ) {
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        if ($this->provider instanceof LeagueBootableServiceProviderInterface) {
            $this->provider->boot();
        }
    }

    /**
     * @return \Cake\Container\DefinitionContainerInterface
     */
    public function getContainer(): CakeDefinitionContainerInterface
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->provider->getIdentifier();
    }

    /**
     * @param string $id
     * @return bool
     */
    public function provides(string $id): bool
    {
        return $this->provider->provides($id);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->provider->register();
    }

    /**
     * @param mixed $container
     * @return \Cake\Container\ContainerAwareInterface
     */
    public function setContainer(mixed $container): CakeContainerAwareInterface
    {
        if (!$container instanceof CakeDefinitionContainerInterface && !$container instanceof LeagueDefinitionContainerInterface) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected container type. Expected `%s` or `%s`, got `%s` instead.',
                CakeDefinitionContainerInterface::class,
                LeagueDefinitionContainerInterface::class,
                get_debug_type($container),
            ));
        }

        $this->provider->setContainer($this->container);

        return $this;
    }

    /**
     * @param string $id
     * @return \Cake\Container\ServiceProvider\ServiceProviderInterface
     */
    public function setIdentifier(string $id): CakeServiceProviderInterface
    {
        $this->provider->setIdentifier($id);

        return $this;
    }
}
