<?php
/**
 * Exception Renderer
 *
 * Provides Exception rendering features.  Which allow exceptions to be rendered
 * as HTML pages.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.test_app.Plugin.TestPlugin.Lib.Error
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ExceptionRenderer', 'Error');

class TestPluginExceptionRenderer extends ExceptionRenderer {

/**
 * Renders the response for the exception.
 *
 * @return void
 */
	public function render() {
		echo 'Rendered by test plugin';
	}
}
