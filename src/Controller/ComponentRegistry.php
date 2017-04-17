<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;

/**
 * ComponentRegistry is a registry for loaded components
 *
 * Handles loading, constructing and binding events for component class objects.
 */
class ComponentRegistry extends ObjectRegistry implements EventDispatcherInterface
{

    use EventDispatcherTrait;

    /**
     * The controller that this collection was initialized with.
     *
     * @var \Cake\Controller\Controller
     */
    protected $_Controller;

    /**
     * Constructor.
     *
     * @param \Cake\Controller\Controller|null $controller Controller instance.
     */
    public function __construct(Controller $controller = null)
    {
        if ($controller) {
            $this->setController($controller);
        }
    }

    /**
     * Get the controller associated with the collection.
     *
     * @return \Cake\Controller\Controller Controller instance
     */
    public function getController()
    {
        return $this->_Controller;
    }

    /**
     * Set the controller associated with the collection.
     *
     * @param \Cake\Controller\Controller $controller Controller instance.
     * @return void
     */
    public function setController(Controller $controller)
    {
        $this->_Controller = $controller;
        $this->eventManager($controller->eventManager());
    }

    /**
     * Resolve a component classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Controller/Component', 'Component');
    }

    /**
     * Throws an exception when a component is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string $plugin The plugin the component is missing in.
     * @return void
     * @throws \Cake\Controller\Exception\MissingComponentException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new MissingComponentException([
            'class' => $class . 'Component',
            'plugin' => $plugin
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
     */
    protected function _create($class, $alias, $config)
    {
        $instance = new $class($this, $config);
        $enable = isset($config['enabled']) ? $config['enabled'] : true;
        if ($enable) {
            $this->eventManager()->on($instance);
        }

        return $instance;
    }
}
