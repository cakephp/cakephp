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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventListenerInterface;
use RuntimeException;

/**
 * Acts as a registry/factory for objects.
 *
 * Provides registry & factory functionality for object types. Used
 * as a super class for various composition based re-use features in CakePHP.
 *
 * Each subclass needs to implement the various abstract methods to complete
 * the template method load().
 *
 * The ObjectRegistry is EventManager aware, but each extending class will need to use
 * \Cake\Event\EventDispatcherTrait to attach and detach on set and bind
 *
 * @see \Cake\Controller\ComponentRegistry
 * @see \Cake\View\HelperRegistry
 * @see \Cake\Console\TaskRegistry
 */
abstract class ObjectRegistry
{

    /**
     * Map of loaded objects.
     *
     * @var object[]
     */
    protected $_loaded = [];

    /**
     * Loads/constructs an object instance.
     *
     * Will return the instance in the registry if it already exists.
     * If a subclass provides event support, you can use `$config['enabled'] = false`
     * to exclude constructed objects from being registered for events.
     *
     * Using Cake\Controller\Controller::$components as an example. You can alias
     * an object by setting the 'className' key, i.e.,
     *
     * ```
     * public $components = [
     *   'Email' => [
     *     'className' => '\App\Controller\Component\AliasedEmailComponent'
     *   ];
     * ];
     * ```
     *
     * All calls to the `Email` component would use `AliasedEmail` instead.
     *
     * @param string $objectName The name/class of the object to load.
     * @param array $config Additional settings to use when loading the object.
     * @return mixed
     */
    public function load($objectName, $config = [])
    {
        if (is_array($config) && isset($config['className'])) {
            $name = $objectName;
            $objectName = $config['className'];
        } else {
            list(, $name) = pluginSplit($objectName);
        }

        $loaded = isset($this->_loaded[$name]);
        if ($loaded && !empty($config)) {
            $this->_checkDuplicate($name, $config);
        }
        if ($loaded) {
            return $this->_loaded[$name];
        }

        $className = $this->_resolveClassName($objectName);
        if (!$className || (is_string($className) && !class_exists($className))) {
            list($plugin, $objectName) = pluginSplit($objectName);
            $this->_throwMissingClassError($objectName, $plugin);
        }
        $instance = $this->_create($className, $name, $config);
        $this->_loaded[$name] = $instance;

        return $instance;
    }

    /**
     * Check for duplicate object loading.
     *
     * If a duplicate is being loaded and has different configuration, that is
     * bad and an exception will be raised.
     *
     * An exception is raised, as replacing the object will not update any
     * references other objects may have. Additionally, simply updating the runtime
     * configuration is not a good option as we may be missing important constructor
     * logic dependent on the configuration.
     *
     * @param string $name The name of the alias in the registry.
     * @param array $config The config data for the new instance.
     * @return void
     * @throws \RuntimeException When a duplicate is found.
     */
    protected function _checkDuplicate($name, $config)
    {
        $existing = $this->_loaded[$name];
        $msg = sprintf('The "%s" alias has already been loaded', $name);
        $hasConfig = method_exists($existing, 'config');
        if (!$hasConfig) {
            throw new RuntimeException($msg);
        }
        if (empty($config)) {
            return;
        }
        $existingConfig = $existing->config();
        unset($config['enabled'], $existingConfig['enabled']);

        $fail = false;
        foreach ($config as $key => $value) {
            if (!array_key_exists($key, $existingConfig)) {
                $fail = true;
                break;
            }
            if (isset($existingConfig[$key]) && $existingConfig[$key] !== $value) {
                $fail = true;
                break;
            }
        }
        if ($fail) {
            $msg .= ' with the following config: ';
            $msg .= var_export($existingConfig, true);
            $msg .= ' which differs from ' . var_export($config, true);
            throw new RuntimeException($msg);
        }
    }

    /**
     * Should resolve the classname for a given object type.
     *
     * @param string $class The class to resolve.
     * @return string|false The resolved name or false for failure.
     */
    abstract protected function _resolveClassName($class);

    /**
     * Throw an exception when the requested object name is missing.
     *
     * @param string $class The class that is missing.
     * @param string $plugin The plugin $class is missing from.
     * @return void
     * @throws \Exception
     */
    abstract protected function _throwMissingClassError($class, $plugin);

    /**
     * Create an instance of a given classname.
     *
     * This method should construct and do any other initialization logic
     * required.
     *
     * @param string $class The class to build.
     * @param string $alias The alias of the object.
     * @param array $config The Configuration settings for construction
     * @return mixed
     */
    abstract protected function _create($class, $alias, $config);

    /**
     * Get the list of loaded objects.
     *
     * @return array List of object names.
     */
    public function loaded()
    {
        return array_keys($this->_loaded);
    }

    /**
     * Check whether or not a given object is loaded.
     *
     * @param string $name The object name to check for.
     * @return bool True is object is loaded else false.
     */
    public function has($name)
    {
        return isset($this->_loaded[$name]);
    }

    /**
     * Get loaded object instance.
     *
     * @param string $name Name of object.
     * @return object|null Object instance if loaded else null.
     */
    public function get($name)
    {
        if (isset($this->_loaded[$name])) {
            return $this->_loaded[$name];
        }

        return null;
    }

    /**
     * Provide public read access to the loaded objects
     *
     * @param string $name Name of property to read
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Provide isset access to _loaded
     *
     * @param string $name Name of object being checked.
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_loaded[$name]);
    }

    /**
     * Normalizes an object array, creates an array that makes lazy loading
     * easier
     *
     * @param array $objects Array of child objects to normalize.
     * @return array Array of normalized objects.
     */
    public function normalizeArray($objects)
    {
        $normal = [];
        foreach ($objects as $i => $objectName) {
            $config = [];
            if (!is_int($i)) {
                $config = (array)$objectName;
                $objectName = $i;
            }
            list(, $name) = pluginSplit($objectName);
            $normal[$name] = ['class' => $objectName, 'config' => $config];
        }

        return $normal;
    }

    /**
     * Clear loaded instances in the registry.
     *
     * If the registry subclass has an event manager, the objects will be detached from events as well.
     *
     * @return void
     */
    public function reset()
    {
        foreach (array_keys($this->_loaded) as $name) {
            $this->unload($name);
        }
    }

    /**
     * Set an object directly into the registry by name.
     *
     * If this collection implements events, the passed object will
     * be attached into the event manager
     *
     * @param string $objectName The name of the object to set in the registry.
     * @param object $object instance to store in the registry
     * @return void
     */
    public function set($objectName, $object)
    {
        list(, $name) = pluginSplit($objectName);
        $this->unload($objectName);
        if ($this instanceof EventDispatcherInterface && $object instanceof EventListenerInterface) {
            $this->eventManager()->on($object);
        }
        $this->_loaded[$name] = $object;
    }

    /**
     * Remove an object from the registry.
     *
     * If this registry has an event manager, the object will be detached from any events as well.
     *
     * @param string $objectName The name of the object to remove from the registry.
     * @return void
     */
    public function unload($objectName)
    {
        if (empty($this->_loaded[$objectName])) {
            return;
        }
        $object = $this->_loaded[$objectName];
        if ($this instanceof EventDispatcherInterface && $object instanceof EventListenerInterface) {
            $this->eventManager()->off($object);
        }
        unset($this->_loaded[$objectName]);
    }

    /**
     * Debug friendly object properties.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $properties = get_object_vars($this);
        if (isset($properties['_loaded'])) {
            $properties['_loaded'] = array_keys($properties['_loaded']);
        }

        return $properties;
    }
}
