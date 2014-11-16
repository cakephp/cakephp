<%
/**
 * Tests bootstrap file
 *
 * Allow a plugin to run its own tests whether CakePHP is installed directly
 * as a vendor for the plugin, or if the plugin has itself been installed as
 * a dependency for an application
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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
%>
<?php
/**
 * Test suite bootstrap for <%= $plugin %>.
 */
function find_root($root) {
	do {
		$lastRoot = $root;
		$root = dirname($root);
		if (is_dir($root . '/vendor/cakephp/cakephp')) {
			return $root;
		}
	} while($root !== $lastRoot);

	throw new Exception("Cannot find the root of the application, unable to run tests");
}

$root = find_root(__FILE__);

chdir($root);
require $root . '/config/bootstrap.php';
