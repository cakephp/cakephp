<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link		  https://cakephp.org CakePHP(tm) Project
 * @package		  Cake.Test.TestApp.Routing.Filter
 * @since		  CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DispatcherFilter', 'Routing');

/**
 * Test2DispatcherFilter
 *
 * @package		  Cake.Test.TestApp.Routing.Filter
 */
class Test2DispatcherFilter extends DispatcherFilter {

	public function beforeDispatch(CakeEvent $event) {
		$event->data['response']->statusCode(500);
		$event->stopPropagation();
		return $event->data['response'];
	}

	public function afterDispatch(CakeEvent $event) {
		$event->data['response']->statusCode(200);
	}

}
