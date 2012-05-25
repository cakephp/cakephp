<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @package		  Cake.Test.test_app.Routing.Filter
 * @since		  CakePHP(tm) v 2.2
 * @license		  MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DispatcherFilter', 'Routing');

class Test2DispatcherFilter extends DispatcherFilter {

	public function beforeDispatch($event) {
		$event->data['response']->statusCode(500);
		$event->stopPropagation();
		return $event->data['response'];
	}

	public function afterDispatch($event) {
		$event->data['response']->statusCode(200);
	}

}