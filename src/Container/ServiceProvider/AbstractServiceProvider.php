<?php
declare(strict_types=1);

namespace Cake\Container\ServiceProvider;

use Cake\Container\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var string|null
     */
    protected ?string $identifier = null;

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return $this->identifier ?? static::class;
    }

    /**
     * @inheritDoc
     */
    public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;

        return $this;
    }
}
