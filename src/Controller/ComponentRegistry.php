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
use Cake\Core\Exception\CakeException;
use Cake\Core\ObjectRegistry;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;

/**
 * ComponentRegistry is a registry for loaded components
 *
 * Handles loading, constructing and binding events for component class objects.
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Controller\Component>
 */
class ComponentRegistry extends ObjectRegistry implements EventDispatcherInterface
{
    use EventDispatcherTrait;

    /**
     * The controller that this collection was initialized with.
     *
     * @var \Cake\Controller\Controller|null
     */
    protected $_Controller;

    /**
     * Constructor.
     *
     * @param \Cake\Controller\Controller|null $controller Controller instance.
     */
    public function __construct(?Controller $controller = null)
    {
        if ($controller) {
            $this->setController($controller);
        }
    }

    /**
     * Get the controller associated with the collection.
     *
     * @return \Cake\Controller\Controller Controller instance or null if not set.
     */
    public function getController(): Controller
    {
        if ($this->_Controller === null) {
            throw new CakeException('Controller not set for ComponentRegistry');
        }

        return $this->_Controller;
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
     * Resolve a component classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|null Either the correct class name or null.
     * @psalm-return class-string|null
     */
    protected function _resolveClassName(string $class): ?string
    {
        return App::className($class, 'Controller/Component', 'Component');
    }

    /**
     * Throws an exception when a component is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     * and Cake\Core\ObjectRegistry::unload()
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
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     * Enabled components will be registered with the event manager.
     *
     * @param string $class The classname to create.
     * @param string $alias The alias of the component.
     * @param array $config An array of config to use for the component.
     * @return \Cake\Controller\Component The constructed component class.
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-param class-string $class
     */
    protected function _create($class, string $alias, array $config): Component
    {
        /** @var \Cake\Controller\Component $instance */
        $instance = new $class($this, $config);
        $enable = $config['enabled'] ?? true;
        if ($enable) {
            $this->getEventManager()->on($instance);
        }

        return $instance;
    }
}
