<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Provides debug information on all timers used in a request.
 *
 */
class TimerPanel extends DebugPanel {

/**
 * startup - add in necessary helpers
 *
 * @param Controller $controller
 * @return void
 */
	public function startup(Controller $controller) {
		if (!in_array('Number', array_keys(HelperCollection::normalizeObjectArray($controller->helpers)))) {
			$controller->helpers[] = 'Number';
		}
		if (!in_array('SimpleGraph', array_keys(HelperCollection::normalizeObjectArray($controller->helpers)))) {
			$controller->helpers[] = 'DebugKit.SimpleGraph';
		}
	}
}
