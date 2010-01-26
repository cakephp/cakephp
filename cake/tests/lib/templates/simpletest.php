<?php
/**
 * Missing SimpleTest error page.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
?>
<?php include dirname(__FILE__) . DS . 'header.php'; ?>
<div id="content">
	<h2>SimpleTest is not installed</h2>
	<p>You must install SimpleTest to use the CakePHP(tm) Test Suite.</p>
	<p>SimpleTest can be placed in one of the following directories.</p>
	<ul>
		<li><?php echo CAKE; ?>vendors </li>
		<li><?php echo APP_DIR . DS; ?>vendors</li>
	</ul>
	<p><a href="http://simpletest.org/en/download.html" target="_blank">Download SimpleTest</a></p>
</div>
<?php include dirname(__FILE__) . DS . 'footer.php'; ?>