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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use ReflectionClass;
use ReflectionMethod;

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
 * methods. These methods can be made accessible on the tables the
 * behavior has been added to by setting the `implementedMethods`
 * config to `true` or a method map when adding the behavior.
 *
 * ```
 * function doSomething($arg1, $arg2) {
 *   // do something
 * }
 * ```
 *
 * Would be called like `$table->doSomething($arg1, $arg2);` when the
 * behavior is added with `['implementedMethods' => true]`.
 *
 * ### Callback methods
 *
 * Behaviors can listen to any events fired on a Table. By default,
 * CakePHP provides a number of lifecycle events your behaviors can
 * listen to:
 *
 * - `beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, boolean $primary)`
 *   Fired before each find operation. By stopping the event and supplying a
 *   return value you can bypass the find operation entirely. Any changes done
 *   to the $query instance will be retained for the rest of the find. The
 *   $primary parameter indicates whether this is the root query
 *   or an associated query.
 *
 * - `buildValidator(EventInterface $event, Validator $validator, string $name)`
 *   Fired when the validator object identified by $name is being built. You can use this
 *   callback to add validation rules or add validation providers.
 *
 * - `buildRules(EventInterface $event, RulesChecker $rules)`
 *   Fired when the rules checking object for the table is being built. You can use this
 *   callback to add more rules to the set.
 *
 * - `beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, $operation)`
 *   Fired before an entity is validated using by a rules checker. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `afterRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, bool $result, $operation)`
 *   Fired after the rules have been checked on the entity. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before each entity is saved. Stopping this event will abort the save
 *   operation. When the event is stopped the result of the event will be returned.
 *
 * - `afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity is saved.
 *
 * - `beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before an entity is deleted. By stopping this event you will abort
 *   the delete operation.
 *
 * - `afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity has been deleted.
 *
 * In addition to the core events, behaviors can respond to any
 * event fired from your Table classes including custom application
 * specific ones.
 *
 * You can set the priority of behaviors' callbacks by using the
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
 * findSlugged(SelectQuery $query, array $options)
 * ```
 *
 * @see \Cake\ORM\Table::addBehavior()
 * @see \Cake\Event\EventManager
 */
class Behavior implements EventListenerInterface
{
    use InstanceConfigTrait;

    /**
     * Reflection method cache for behaviors.
     *
     * Stores the reflected method + finder methods per class.
     * This prevents reflecting the same class multiple times in a single process.
     *
     * @var array<string, array>
     */
    protected static array $reflectionCache = [];

    /**
     * Default configuration
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [];

    /**
     * Cached, normalized map of proxied methods.
     *
     * Generated on first read and reused for subsequent calls.
     *
     * @var array<string, string>|null
     */
    private ?array $implementedMethods = null;

    /**
     * Constructor
     *
     * Merges config with the default and store in the config property
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array<string, mixed> $config The config for this behavior.
     */
    public function __construct(protected Table $table, array $config = [])
    {
        $config = $this->resolveMethodAliases(
            'implementedFinders',
            $this->defaultConfig,
            $config,
        );
        $config = $this->resolveMethodAliases(
            'implementedMethods',
            $this->defaultConfig,
            $config,
        );
        $this->setConfig($config);
        $this->initialize($config);
    }

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @param array<string, mixed> $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config): void {}

    /**
     * Get the table instance this behavior is bound to.
     *
     * @return \Cake\ORM\Table The bound table instance.
     */
    public function table(): Table
    {
        return $this->table;
    }

    /**
     * Removes aliased methods that would otherwise be duplicated by userland configuration.
     *
     * @param string $key The key to filter.
     * @param array<string, mixed> $defaults The default method mappings.
     * @param array<string, mixed> $config The customized method mappings.
     * @return array A de-duped list of config data.
     */
    protected function resolveMethodAliases(string $key, array $defaults, array $config): array
    {
        if (!isset($defaults[$key], $config[$key])) {
            return $config;
        }
        if (!is_array($defaults[$key]) || !is_array($config[$key])) {
            return $config;
        }
        if ($config[$key] === []) {
            $this->setConfig($key, [], false);
            unset($config[$key]);

            return $config;
        }

        $indexed = array_flip($defaults[$key]);
        $indexedCustom = array_flip($config[$key]);
        foreach ($indexed as $method => $alias) {
            $indexedCustom[$method] ??= $alias;
        }
        $this->setConfig($key, array_flip($indexedCustom), false);
        unset($config[$key]);

        return $config;
    }

    /**
     * verifyConfig
     *
     * Checks that implemented keys contain values pointing at callable.
     *
     * @return void
     * @throws \Cake\Core\Exception\CakeException if config are invalid
     */
    public function verifyConfig(): void
    {
        $keys = ['implementedFinders', 'implementedMethods'];
        foreach ($keys as $key) {
            if (!isset($this->config[$key]) || !is_array($this->config[$key])) {
                continue;
            }

            foreach ($this->config[$key] as $method) {
                if (!is_callable([$this, $method])) {
                    throw new CakeException(sprintf(
                        'The method `%s` is not callable on class `%s`.',
                        $method,
                        static::class,
                    ));
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
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.afterMarshal' => 'afterMarshal',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.afterSaveCommit' => 'afterSaveCommit',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.afterDeleteCommit' => 'afterDeleteCommit',
            'Model.buildValidator' => 'buildValidator',
            'Model.buildRules' => 'buildRules',
            'Model.beforeRules' => 'beforeRules',
            'Model.afterRules' => 'afterRules',
        ];
        $config = $this->getConfig();
        $priority = $config['priority'] ?? null;
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
                    'priority' => $priority,
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
     * With the above example, a call to `$table->find('this')` will call `$behavior->findThis()`
     * and a call to `$table->find('alias')` will call `$behavior->findMethodName()`
     *
     * It is recommended, though not required, to define implementedFinders in the config property
     * of child classes such that it is not necessary to use reflections to derive the available
     * method list. See core behaviors for examples
     *
     * @return array
     * @throws \ReflectionException
     */
    public function implementedFinders(): array
    {
        $methods = $this->getConfig('implementedFinders');
        if ($methods !== null) {
            return $methods;
        }

        return $this->reflectionCache()['finders'];
    }

    /**
     * Provides an alias -> methodname map of which methods a behavior proxies on
     * the tables they are attached to. Example:
     *
     * ```
     *  [
     *    'method' => 'method',
     *    'aliasedMethod' => 'somethingElse'
     *  ]
     * ```
     *
     * With the above example, a call to `$table->method()` will call `$behavior->method()`
     * and a call to `$table->aliasedMethod()` will call `$behavior->somethingElse()`
     *
     * Proxying of behavior methods through the table instance is disabled by
     * default. To enable it, set the `implementedMethods` config to `true` or
     * a method map when adding the behavior. When set to `true` the list of
     * proxied methods is derived using reflection. Methods declared on
     * `Cake\ORM\Behavior`, event callback methods and finder methods are
     * excluded from the reflected list.
     *
     * The config can also be a numerically indexed list of method names, in
     * which case each method is proxied under its own name. That is, the list
     *
     * ```
     *  ['method', 'somethingElse']
     * ```
     *
     * is equivalent to the map
     *
     * ```
     *  ['method' => 'method', 'somethingElse' => 'somethingElse']
     * ```
     *
     * The generated map is normalized: alias keys and method names are
     * lowercased, as method resolution is case-insensitive. Consumers can
     * compare against it without having to normalize case themselves.
     *
     * The generated map is memoized on first read. Later changes to the
     * `implementedMethods` config do not refresh it.
     *
     * @return array<string, string>
     * @throws \ReflectionException
     */
    public function implementedMethods(): array
    {
        if ($this->implementedMethods !== null) {
            return $this->implementedMethods;
        }

        $methods = $this->getConfig('implementedMethods');
        if ($methods === true) {
            $methods = $this->reflectionCache()['methods'];
        } elseif (!is_array($methods)) {
            $methods = [];
        }

        // Support numerically indexed method names, where each value is used as its own alias key.
        $map = [];
        foreach ($methods as $key => $method) {
            $key = is_int($key) ? $method : $key;
            $map[strtolower($key)] = strtolower($method);
        }

        return $this->implementedMethods = $map;
    }

    /**
     * Gets the methods implemented by this behavior
     *
     * Uses the implementedEvents() method to exclude callback methods.
     * Methods starting with `_` will be ignored, as will methods
     * declared on Cake\ORM\Behavior
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function reflectionCache(): array
    {
        $class = static::class;
        if (isset(self::$reflectionCache[$class])) {
            return self::$reflectionCache[$class];
        }

        $events = $this->implementedEvents();
        $eventMethods = [];
        foreach ($events as $binding) {
            if (is_array($binding) && isset($binding['callable'])) {
                $callable = $binding['callable'];
                assert(is_string($callable));
                $binding = $callable;
            }
            $eventMethods[$binding] = true;
        }

        $baseClass = self::class;
        if (isset(self::$reflectionCache[$baseClass])) {
            $baseMethods = self::$reflectionCache[$baseClass];
        } else {
            $baseMethods = get_class_methods($baseClass);
            self::$reflectionCache[$baseClass] = $baseMethods;
        }

        $return = [
            'finders' => [],
            'methods' => [],
        ];

        $reflection = new ReflectionClass($class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            if (
                in_array($methodName, $baseMethods, true)
                || isset($eventMethods[$methodName])
            ) {
                continue;
            }

            if (str_starts_with($methodName, 'find')) {
                $return['finders'][lcfirst(substr($methodName, 4))] = $methodName;
            } else {
                $return['methods'][$methodName] = $methodName;
            }
        }

        return self::$reflectionCache[$class] = $return;
    }
}
