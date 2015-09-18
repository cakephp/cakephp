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
namespace Cake\ORM;

use Cake\Core\Exception\Exception;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;

/**
 * Base class for behaviors.
 *
 * Behaviors allow you to simulate mixins, and create
 * reusable blocks of application logic, that can be reused across
 * several models. Behaviors also provide a way to hook into model
 * callbacks and augment their behavior.
 *
 * ### Mixin methods
 *
 * Behaviors can provide mixin like features by declaring public
 * methods. These methods will be accessible on the tables the
 * behavior has been added to.
 *
 * ```
 * function doSomething($arg1, $arg2) {
 *   // do something
 * }
 * ```
 *
 * Would be called like `$table->doSomething($arg1, $arg2);`.
 *
 * ### Callback methods
 *
 * Behaviors can listen to any events fired on a Table. By default
 * CakePHP provides a number of lifecycle events your behaviors can
 * listen to:
 *
 * - `beforeFind(Event $event, Query $query, ArrayObject $options, boolean $primary)`
 *   Fired before each find operation. By stopping the event and supplying a
 *   return value you can bypass the find operation entirely. Any changes done
 *   to the $query instance will be retained for the rest of the find. The
 *   $primary parameter indicates whether or not this is the root query,
 *   or an associated query.
 *
 * - `buildValidator(Event $event, Validator $validator, string $name)`
 *   Fired when the validator object identified by $name is being built. You can use this
 *   callback to add validation rules or add validation providers.
 *
 * - `buildRules(Event $event, RulesChecker $rules)`
 *   Fired when the rules checking object for the table is being built. You can use this
 *   callback to add more rules to the set.
 *
 * - `beforeRules(Event $event, EntityInterface $entity, ArrayObject $options, $operation)`
 *   Fired before an entity is validated using by a rules checker. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `afterRules(Event $event, EntityInterface $entity, ArrayObject $options, bool $result, $operation)`
 *   Fired after the rules have been checked on the entity. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before each entity is saved. Stopping this event will abort the save
 *   operation. When the event is stopped the result of the event will be returned.
 *
 * - `afterSave(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity is saved.
 *
 * - `beforeDelete(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before an entity is deleted. By stopping this event you will abort
 *   the delete operation.
 *
 * - `afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity has been deleted.
 *
 * In addition to the core events, behaviors can respond to any
 * event fired from your Table classes including custom application
 * specific ones.
 *
 * You can set the priority of a behaviors callbacks by using the
 * `priority` setting when attaching a behavior. This will set the
 * priority for all the callbacks a behavior provides.
 *
 * ### Finder methods
 *
 * Behaviors can provide finder methods that hook into a Table's
 * find() method. Custom finders are a great way to provide preset
 * queries that relate to your behavior. For example a SluggableBehavior
 * could provide a find('slugged') finder. Behavior finders
 * are implemented the same as other finders. Any method
 * starting with `find` will be setup as a finder. Your finder
 * methods should expect the following arguments:
 *
 * ```
 * findSlugged(Query $query, array $options)
 * ```
 *
 * @see \Cake\ORM\Table::addBehavior()
 * @see \Cake\Event\EventManager
 */
class Behavior implements EventListenerInterface
{

    use InstanceConfigTrait;

    /**
     * Table instance.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Reflection method cache for behaviors.
     *
     * Stores the reflected method + finder methods per class.
     * This prevents reflecting the same class multiple times in a single process.
     *
     * @var array
     */
    protected static $_reflectionCache = [];

    /**
     * Default configuration
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Constructor
     *
     * Merges config with the default and store in the config property
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        $config = $this->_resolveMethodAliases(
            'implementedFinders',
            $this->_defaultConfig,
            $config
        );
        $config = $this->_resolveMethodAliases(
            'implementedMethods',
            $this->_defaultConfig,
            $config
        );
        $this->_table = $table;
        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @param array $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * Removes aliased methods that would otherwise be duplicated by userland configuration.
     *
     * @param string $key The key to filter.
     * @param array $defaults The default method mappings.
     * @param array $config The customized method mappings.
     * @return array A de-duped list of config data.
     */
    protected function _resolveMethodAliases($key, $defaults, $config)
    {
        if (!isset($defaults[$key], $config[$key])) {
            return $config;
        }
        if (isset($config[$key]) && $config[$key] === []) {
            $this->config($key, [], false);
            unset($config[$key]);
            return $config;
        }

        $indexed = array_flip($defaults[$key]);
        $indexedCustom = array_flip($config[$key]);
        foreach ($indexed as $method => $alias) {
            if (!isset($indexedCustom[$method])) {
                $indexedCustom[$method] = $alias;
            }
        }
        $this->config($key, array_flip($indexedCustom), false);
        unset($config[$key]);
        return $config;
    }

    /**
     * verifyConfig
     *
     * Checks that implemented keys contain values pointing at callable.
     *
     * @return void
     * @throws \Cake\Core\Exception\Exception if config are invalid
     */
    public function verifyConfig()
    {
        $keys = ['implementedFinders', 'implementedMethods'];
        foreach ($keys as $key) {
            if (!isset($this->_config[$key])) {
                continue;
            }

            foreach ($this->_config[$key] as $method) {
                if (!is_callable([$this, $method])) {
                    throw new Exception(sprintf('The method %s is not callable on class %s', $method, get_class($this)));
                }
            }
        }
    }

    /**
     * Gets the Model callbacks this behavior is interested in.
     *
     * By defining one of the callback methods a behavior is assumed
     * to be interested in the related event.
     *
     * Override this method if you need to add non-conventional event listeners.
     * Or if you want your behavior to listen to non-standard events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        $eventMap = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.buildValidator' => 'buildValidator',
            'Model.buildRules' => 'buildRules',
            'Model.beforeRules' => 'beforeRules',
            'Model.afterRules' => 'afterRules',
        ];
        $config = $this->config();
        $priority = isset($config['priority']) ? $config['priority'] : null;
        $events = [];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            if ($priority === null) {
                $events[$event] = $method;
            } else {
                $events[$event] = [
                    'callable' => $method,
                    'priority' => $priority
                ];
            }
        }
        return $events;
    }

    /**
     * implementedFinders
     *
     * Provides an alias->methodname map of which finders a behavior implements. Example:
     *
     * ```
     *  [
     *    'this' => 'findThis',
     *    'alias' => 'findMethodName'
     *  ]
     * ```
     *
     * With the above example, a call to `$Table->find('this')` will call `$Behavior->findThis()`
     * and a call to `$Table->find('alias')` will call `$Behavior->findMethodName()`
     *
     * It is recommended, though not required, to define implementedFinders in the config property
     * of child classes such that it is not necessary to use reflections to derive the available
     * method list. See core behaviors for examples
     *
     * @return array
     */
    public function implementedFinders()
    {
        $methods = $this->config('implementedFinders');
        if (isset($methods)) {
            return $methods;
        }

        return $this->_reflectionCache()['finders'];
    }

    /**
     * implementedMethods
     *
     * Provides an alias->methodname map of which methods a behavior implements. Example:
     *
     * ```
     *  [
     *    'method' => 'method',
     *    'aliasedmethod' => 'somethingElse'
     *  ]
     * ```
     *
     * With the above example, a call to `$Table->method()` will call `$Behavior->method()`
     * and a call to `$Table->aliasedmethod()` will call `$Behavior->somethingElse()`
     *
     * It is recommended, though not required, to define implementedFinders in the config property
     * of child classes such that it is not necessary to use reflections to derive the available
     * method list. See core behaviors for examples
     *
     * @return array
     */
    public function implementedMethods()
    {
        $methods = $this->config('implementedMethods');
        if (isset($methods)) {
            return $methods;
        }

        return $this->_reflectionCache()['methods'];
    }

    /**
     * Gets the methods implemented by this behavior
     *
     * Uses the implementedEvents() method to exclude callback methods.
     * Methods starting with `_` will be ignored, as will methods
     * declared on Cake\ORM\Behavior
     *
     * @return array
     */
    protected function _reflectionCache()
    {
        $class = get_class($this);
        if (isset(self::$_reflectionCache[$class])) {
            return self::$_reflectionCache[$class];
        }

        $events = $this->implementedEvents();
        $eventMethods = [];
        foreach ($events as $e => $binding) {
            if (is_array($binding) && isset($binding['callable'])) {
                $binding = $binding['callable'];
            }
            $eventMethods[$binding] = true;
        }

        $baseClass = 'Cake\ORM\Behavior';
        if (isset(self::$_reflectionCache[$baseClass])) {
            $baseMethods = self::$_reflectionCache[$baseClass];
        } else {
            $baseMethods = get_class_methods($baseClass);
            self::$_reflectionCache[$baseClass] = $baseMethods;
        }

        $return = [
            'finders' => [],
            'methods' => []
        ];

        $reflection = new \ReflectionClass($class);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            if (in_array($methodName, $baseMethods) ||
                isset($eventMethods[$methodName])
            ) {
                continue;
            }

            if (substr($methodName, 0, 4) === 'find') {
                $return['finders'][lcfirst(substr($methodName, 4))] = $methodName;
            } else {
                $return['methods'][$methodName] = $methodName;
            }
        }

        return self::$_reflectionCache[$class] = $return;
    }
}
