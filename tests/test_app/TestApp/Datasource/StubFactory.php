<?php
declare(strict_types=1);

namespace TestApp\Datasource;

use Cake\Datasource\Locator\LocatorInterface;
use Cake\Datasource\RepositoryInterface;

class StubFactory implements LocatorInterface
{
    private $instances = [];

    /**
     * @inheritDoc
     */
    public function get(string $alias, array $options = [])
    {
        if (!isset($this->instances[$alias])) {
            return false;
        }

        return $this->instances[$alias];
    }

    /**
     * @inheritDoc
     */
    public function set(string $alias, RepositoryInterface $repository)
    {
        $this->instances[$alias] = $repository;
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
