<?php
declare(strict_types=1);

namespace Cake\Container\ServiceProvider;

use Cake\Container\ContainerAwareInterface;
use IteratorAggregate;

/**
 * @template-extends \IteratorAggregate<string, \Cake\Container\ServiceProvider\ServiceProviderInterface>
 */
interface ServiceProviderAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    /**
     * @param \Cake\Container\ServiceProvider\ServiceProviderInterface $provider
     * @return $this
     */
    public function add(ServiceProviderInterface $provider): ServiceProviderAggregateInterface;

    /**
     * @param string $id
     * @return bool
     */
    public function provides(string $id): bool;

    /**
     * @param string $service
     * @return void
     */
    public function register(string $service): void;
}
