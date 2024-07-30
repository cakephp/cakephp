<?php
declare(strict_types=1);

namespace TestApp\Datasource;

use Cake\Datasource\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class FakeConnection implements ConnectionInterface
{
    /**
     * Constructor.
     *
     * @param array $_config configuration for connecting to database
     */
    public function __construct(protected $_config = [])
    {
    }

    /**
     * Returns the set config
     */
    public function config(): array
    {
        return $this->_config;
    }

    /**
     * Returns the set name
     */
    public function configName(): string
    {
        if (empty($this->_config['name'])) {
            return '';
        }

        return $this->_config['name'];
    }

    public function getDriver(string $role = self::ROLE_WRITE): object
    {
        throw new RuntimeException('Not implemented');
    }

    public function getLogger(): LoggerInterface
    {
        throw new RuntimeException('Not implemented');
    }

    public function setLogger(LoggerInterface $logger): void
    {
    }

    public function setCacher(CacheInterface $cacher): never
    {
        throw new RuntimeException('Not implemented');
    }

    public function getCacher(): CacheInterface
    {
        throw new RuntimeException('Not implemented');
    }

    public function enableQueryLogging(bool $enable = true): static
    {
        return $this;
    }

    public function disableQueryLogging(): static
    {
        return $this;
    }

    public function isQueryLoggingEnabled(): bool
    {
        return false;
    }
}
