<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.test_app.plugins.test_plugin_two.vendors.shells
 * @since         CakePHP(tm) v 1.2.0.7871
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
