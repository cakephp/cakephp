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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\App;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\CakeException;
use Cake\Core\ObjectRegistry;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\Argument\LiteralArgument;
use League\Container\Argument\ResolvableArgument;
use League\Container\Exception\NotFoundException;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * ComponentRegistry is a registry for loaded components
 *
 * Handles loading, constructing and binding events for component class objects.
 *
 * @template TSubject of \Cake\Controller\Controller
 * @extends \Cake\Core\ObjectRegistry<\Cake\Controller\Component>
 * @implements \Cake\Event\EventDispatcherInterface<TSubject>
 */
class ComponentRegistry extends ObjectRegistry implements EventDispatcherInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<TSubject>
     */
    use EventDispatcherTrait;

    use ArgumentResolverTrait;

    /**
     * The controller that this collection is associated with.
     *
     * @var \Cake\Controller\Controller|null
     */
    protected ?Controller $_Controller = null;

    /**
     * @var \Cake\Core\ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    /**
     * Constructor.
     *
     * @param \Cake\Controller\Controller|null $controller Controller instance.
     * @param \Cake\Core\ContainerInterface|null $container Container instance.
     */
    public function __construct(?Controller $controller = null, ?ContainerInterface $container = null)
    {
        if ($controller !== null) {
            $this->setController($controller);
        }
        $this->container = $container;
    }

    /**
     * Set the controller associated with the collection.
     *
     * @param \Cake\Controller\Controller $controller Controller instance.
     * @return $this
     */
    public function setController(Controller $controller)
    {
        $this->_Controller = $controller;
        $this->setEventManager($controller->getEventManager());

        return $this;
    }

    /**
     * Get the controller associated with the collection.
     *
     * @return \Cake\Controller\Controller Controller instance.
     */
    public function getController(): Controller
    {
        if ($this->_Controller === null) {
            throw new RuntimeException('Controller must be set first.');
        }

        return $this->_Controller;
    }

    /**
     * Resolve a component classname.
     *
     * Part of the template method for {@link \Cake\Core\ObjectRegistry::load()}.
     *
     * @param string $class Partial classname to resolve.
     * @return class-string<\Cake\Controller\Component>|null Either the correct class name or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Cake\Controller\Component>|null */
        return App::className($class, 'Controller/Component', 'Component');
    }

    /**
     * Throws an exception when a component is missing.
     *
     * Part of the template method for {@link \Cake\Core\ObjectRegistry::load()}
     * and {@link \Cake\Core\ObjectRegistry::unload()}
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the component is missing in.
     * @return void
     * @throws \Cake\Controller\Exception\MissingComponentException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new MissingComponentException([
            'class' => $class . 'Component',
            'plugin' => $plugin,
        ]);
    }

    /**
     * Create the component instance.
     *
     * Part of the template method for {@link \Cake\Core\ObjectRegistry::load()}
     * Enabled components will be registered with the event manager.
     *
     * ## Container Resolution
     *
     * When a container is available, this method attempts to resolve components from it.
     * Components registered in the container will be resolved using dependency injection.
     * If not registered, a new definition will be created with auto-wired constructor arguments.
     *
     * ## Edge Cases
     *
     * - **Shared instances**: Components registered as shared instances in the container
     *   will have their config merged via setConfig(). This means multiple controller
     *   instances may share the same component instance, which could lead to unexpected
     *   state sharing between requests.
     * - **Manual registration**: Components manually registered in the container with
     *   specific constructor arguments will use those arguments. The `$config` parameter
     *   will be merged into the component after instantiation using setConfig().
     *
     * @param \Cake\Controller\Component|class-string<\Cake\Controller\Component> $class The classname to create.
     * @param string $alias The alias of the component.
     * @param array<string, mixed> $config An array of config to use for the component.
     * @return \Cake\Controller\Component The constructed component class.
     */
    protected function _create(object|string $class, string $alias, array $config): Component
    {
        if (is_object($class)) {
            return $class;
        }
        if ($this->container?->has($class)) {
            // Check if definition already exists - if so, user has manually configured it
            $hasDefinition = false;
            try {
                $this->container->extend($class);
                $hasDefinition = true;
            } catch (NotFoundException) {
                // No definition exists yet
            }

            if (!$hasDefinition) {
                // No user-defined configuration - add auto-wired arguments
                $constructor = (new ReflectionClass($class))->getConstructor();
                if ($constructor !== null) {
                    $args = $this->reflectArguments($constructor, ['config' => $config]);
                    $this->container->add($class)->addArguments($args);
                }
            }

            /** @var \Cake\Controller\Component $instance */
            $instance = $this->container->get($class);

            // For manually configured components, merge runtime config
            if ($hasDefinition && $config) {
                $instance->setConfig($config);
            }
        } else {
            $instance = new $class($this, $config);
        }

        if ($config['enabled'] ?? true) {
            $this->getEventManager()->on($instance);
        }

        return $instance;
    }

    /**
     * Get container instance.
     *
     * @return \Cake\Core\ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            throw new CakeException('Container not set.');
        }

        return $this->container;
    }

    /**
     * Reflect on constructor arguments and build argument list for container.
     *
     * This method inspects a constructor's parameters and builds a list of
     * arguments that can be passed to the container's add() or extend() methods.
     *
     * @param \ReflectionFunctionAbstract $method The constructor to reflect on
     * @param array<string, mixed> $args Named arguments to pass as literals (e.g., ['config' => []])
     * @return array<\League\Container\Argument\LiteralArgument|\League\Container\Argument\ResolvableArgument>
     */
    protected function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array
    {
        $arguments = [];
        $params = $method->getParameters();

        foreach ($params as $param) {
            $name = $param->getName();

            // If we have a literal value for this parameter, use it
            if (array_key_exists($name, $args)) {
                $arguments[] = new LiteralArgument($args[$name]);
                continue;
            }

            // Check if parameter has a type hint
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                // Type-hinted parameter - resolve from container
                $arguments[] = new ResolvableArgument($type->getName());
                continue;
            }

            // Check for default value
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = new LiteralArgument($param->getDefaultValue());
                continue;
            }

            // No type hint, no default, no provided value - this will fail at runtime
            $declaringClass = $method instanceof ReflectionMethod
                ? $method->getDeclaringClass()->getName()
                : 'unknown';

            throw new CakeException(
                sprintf(
                    'Cannot auto-wire parameter $%s in %s - no type hint or default value',
                    $name,
                    $declaringClass,
                ),
            );
        }

        return $this->resolveArguments($arguments);
    }
}
