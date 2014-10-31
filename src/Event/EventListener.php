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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

trigger_error('EventListener is deprecated, use EventListenerInterface instead.', E_USER_DEPRECATED);

/**
 * Objects implementing this interface should declare the `implementedEvents` function
 * to notify the event manager what methods should be called when an event is triggered.
 *
 * @deprecated 3.0.0 Use EventListenerInterface instead.
 */
interface EventListener extends EventListenerInterface {
}
