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
use Cake\Core\ObjectRegistry;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
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
            /** @var \Cake\Controller\Component $instance */
            $instance = $this->container->get($class);
            $instance->setConfig($config);
        } else {
            $instance = new $class($this, $config);
        }

        if ($config['enabled'] ?? true) {
            $this->getEventManager()->on($instance);
        }

        return $instance;
    }
}
