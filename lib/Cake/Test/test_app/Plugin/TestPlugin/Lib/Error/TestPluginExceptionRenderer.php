<?php
/**
 * Exception Renderer
 *
 * Provides Exception rendering features. Which allow exceptions to be rendered
 * as HTML pages.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Lib.Error
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ExceptionRenderer', 'Error');

/**
 * Class TestPluginExceptionRenderer
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Lib.Error
 */
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
