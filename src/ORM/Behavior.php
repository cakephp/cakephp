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

use Cake\Core\InstanceConfigTrait;
use Cake\Error\Exception;
use Cake\Event\EventListener;

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
 * {{{
 * function doSomething($arg1, $arg2) {
 *   // do something
 * }
 * }}}
 *
 * Would be called like `$table->doSomething($arg1, $arg2);`.
 *
 * ## Callback methods
 *
 * Behaviors can listen to any events fired on a Table. By default
 * CakePHP provides a number of lifecycle events your behaviors can
 * listen to:
 *
 * - `beforeFind(Event $event, Query $query)`
 *   Fired before a query is converted into SQL.
 *
 * - `beforeDelete(Event $event, Entity $entity)`
 *   Fired before an entity is deleted.
 *
 * - `afterDelete(Event $event, Entity $entity)`
 *   Fired after an entity has been deleted. The entity parameter
 *   will contain the entity state from before it was deleted.
 *
 * - `beforeSave(Event $event, Entity $entity)`
 *   Fired before an entity is saved. In the case where
 *   multiple entities are being saved, one event will be fired
 *   for each entity.
 *
 * - `afterSave(Event $event, Entity $entity)`
 *   Fired after an entity is saved. The saved entity will be provided
 *   as a parameter.
 *
 * In addition to the core events, behaviors can respond to any
 * event fired from your Table classes including custom application
 * specific ones.
 *
 * You can set the priority of a behaviors callbacks by using the
 * `priority` setting when attaching a behavior. This will set the
 * priority for all the callbacks a behavior provides.
 *
 * ## Finder methods
 *
 * Behaviors can provide finder methods that hook into a Table's
 * find() method. Custom finders are a great way to provide preset
 * queries that relate to your behavior. For example a SluggableBehavior
 * could provide a find('slugged') finder. Behavior finders
 * are implemented the same as other finders. Any method
 * starting with `find` will be setup as a finder. Your finder
 * methods should expect the following arguments:
 *
 * {{{
 * findSlugged(Query $query, array $options)
 * }}}
 *
 * @see \Cake\ORM\Table::addBehavior()
 * @see \Cake\Event\EventManager
 */
class Behavior implements EventListener {

	use InstanceConfigTrait;

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
 * Merge config with the default and store in the config property
 *
 * Does not retain a reference to the Table object. If you need this
 * you should override the constructor.
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		$this->config($config);
	}

/**
 * verifyConfig
 *
 * Check that implemented* keys contain values pointing at callable
 *
 * @return void
 * @throws \Cake\Error\Exception if config are invalid
 */
	public function verifyConfig() {
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
 * Get the Model callbacks this behavior is interested in.
 *
 * By defining one of the callback methods a behavior is assumed
 * to be interested in the related event.
 *
 * Override this method if you need to add non-conventional event listeners.
 * Or if you want you behavior to listen to non-standard events.
 *
 * @return array
 */
	public function implementedEvents() {
		$eventMap = [
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.beforeDelete' => 'beforeDelete',
			'Model.afterDelete' => 'afterDelete',
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
 * provides and alias->methodname map of which finders a behavior implements. Example:
 *
 * {{{
 *  [
 *    'this' => 'findThis',
 *    'alias' => 'findMethodName'
 *  ]
 * }}}
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
	public function implementedFinders() {
		if (isset($this->_config['implementedFinders'])) {
			return $this->_config['implementedFinders'];
		}

		$reflectionMethods = $this->_reflectionCache();
		return $reflectionMethods['finders'];
	}

/**
 * implementedMethods
 *
 * provides an alias->methodname map of which methods a behavior implements. Example:
 *
 * {{{
 *  [
 *    'method' => 'method',
 *    'aliasedmethod' => 'somethingElse'
 *  ]
 * }}}
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
	public function implementedMethods() {
		if (isset($this->_config['implementedMethods'])) {
			return $this->_config['implementedMethods'];
		}

		$reflectionMethods = $this->_reflectionCache();
		return $reflectionMethods['methods'];
	}

/**
 * Get the methods implemented by this behavior
 *
 * Use the implementedEvents() method to exclude callback methods.
 * Methods starting with `_` will be ignored, as will methods
 * declared on Cake\ORM\Behavior
 *
 * @return array
 */
	protected function _reflectionCache() {
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
			if (in_array($methodName, $baseMethods)) {
				continue;
			}

			$methodName = $method->getName();
			if (strpos($methodName, '_') === 0 || isset($eventMethods[$methodName])) {
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
