<?php
/**
 * Test Panel of test_app
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Class TestPanel
 *
 * @since         DebugKit 0.1
 */
class TestPanel extends DebugPanel {

/**
 * Startup
 *
 * @param Controller $controller
 */
	public function startup(Controller $controller) {
		$controller->testPanel = true;
	}

}
