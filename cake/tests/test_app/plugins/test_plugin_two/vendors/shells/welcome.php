<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.test_app.plugins.test_plugin_two.vendors.shells
 * @since         CakePHP(tm) v 1.2.0.7871
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
class WelcomeShell extends Shell {

/**
 * say_hello method
 *
 * @access public
 * @return void
 */
	function say_hello() {
		$this->out('This is the say_hello method called from TestPluginTwo.WelcomeShell');
	}
}
