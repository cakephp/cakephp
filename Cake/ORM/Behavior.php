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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Event\EventListener;

/**
 * Base class for behaviors.
 *
 * Defines the Behavior interface, and contains common model interaction
 * functionality. Behaviors allow you to simulate mixins, and create
 * reusable blocks of application logic, that can be reused across
 * several models. Behaviors also provide a way to hook into model
 * callbacks and augment their behavior.
 *
 * ### Mixin methods
 *
 * Behaviors can provide mixin like features by declaring public
 * methods. These methods should expect the Table instance to be
 * shifted onto the parameter list.
 *
 * {{{
 * function doSomething(Table $table, $arg1, $arg2) {
 *		//do something
 * }
 * }}}
 *
 * Would be called like `$this->Table->doSomething($arg1, $arg2);`.
 *
 * ## Callback methods
 *
 */
class Behavior implements EventListener {

/**
 * Contains configuration settings.
 *
 * @var array
 */
	public $settings = [];

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
		$events = [];
		foreach ($eventMap as $event => $method) {
			if (method_exists($this, $method)) {
				$events[$event] = $method;
			}
		}
		return $events;
	}

}
