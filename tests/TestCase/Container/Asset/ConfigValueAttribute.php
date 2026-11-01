<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\ContextualAttributeInterface;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Resolves a config value with fallback to the parameter default.
 * The config array stands in for application configuration
 * (e.g. `Configure::read()`), keeping this split-safe test asset
 * free of framework dependencies.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ConfigValueAttribute implements ContextualAttributeInterface
{
    /**
     * @param string $key Config key to look up.
     * @param array<string, mixed> $config Config values to read from.
     */
    public function __construct(protected string $key, protected array $config = [])
    {
    }

    public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed
    {
        if (array_key_exists($this->key, $this->config)) {
            return $this->config[$this->key];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }
}
