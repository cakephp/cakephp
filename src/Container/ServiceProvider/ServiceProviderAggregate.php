<?php
declare(strict_types=1);

namespace Cake\Container\ServiceProvider;

use Cake\Container\ContainerAwareTrait;
use Cake\Container\Exception\ContainerException;
use Generator;

class ServiceProviderAggregate implements ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    /**
     * @var array<\Cake\Container\ServiceProvider\ServiceProviderInterface>
     */
    protected array $providers = [];

    /**
     * @var array
     */
    protected array $registered = [];

    /**
     * @inheritDoc
     */
    public function add(ServiceProviderInterface $provider): ServiceProviderAggregateInterface
    {
        if (in_array($provider, $this->providers, true)) {
            return $this;
        }

        $provider->setContainer($this->getContainer());

        if ($provider instanceof BootableServiceProviderInterface) {
            $provider->boot();
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function provides(string $id): bool
    {
        foreach ($this->getIterator() as $provider) {
            if ($provider->provides($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Generator
    {
        yield from $this->providers;
    }

    /**
     * @inheritDoc
     */
    public function register(string $service): void
    {
        if ($this->provides($service) === false) {
            throw new ContainerException(
                sprintf('(%s) is not provided by a service provider', $service),
            );
        }

        foreach ($this->getIterator() as $provider) {
            if (in_array($provider->getIdentifier(), $this->registered, true)) {
                continue;
            }

            if ($provider->provides($service)) {
                $provider->register();
                $this->registered[] = $provider->getIdentifier();
            }
        }
    }
}
