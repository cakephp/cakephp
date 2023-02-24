<?php
declare(strict_types=1);

namespace TestApp\Datasource;

use Cake\Datasource\Exception\MissingModelException;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\Datasource\RepositoryInterface;

class StubFactory implements LocatorInterface
{
    private $instances = [];

    /**
     * @inheritDoc
     */
    public function get(string $alias, array $options = []): RepositoryInterface
    {
        if (!isset($this->instances[$alias])) {
            throw new MissingModelException(sprintf(
                'Model class "%s" of type "Test" could not be found.',
                $alias
            ));
        }

        return $this->instances[$alias];
    }

    /**
     * @inheritDoc
     */
    public function set(string $alias, RepositoryInterface $repository): RepositoryInterface
    {
        $this->instances[$alias] = $repository;

        return $repository;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $alias): bool
    {
        return isset($this->instances[$alias]);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $alias): void
    {
        unset($this->instances[$alias]);
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->instances = [];
    }
}
