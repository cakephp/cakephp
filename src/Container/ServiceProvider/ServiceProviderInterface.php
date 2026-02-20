<?php
declare(strict_types=1);

namespace Cake\Container\ServiceProvider;

use Cake\Container\ContainerAwareInterface;

interface ServiceProviderInterface extends ContainerAwareInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param string $id
     * @return bool
     */
    public function provides(string $id): bool;

    /**
     * @return void
     */
    public function register(): void;

    /**
     * @param string $id
     * @return $this
     */
    public function setIdentifier(string $id): ServiceProviderInterface;
}
