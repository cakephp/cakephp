<?php
/**
 * Missing PHPUnit
 * error page.
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
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php include dirname(__FILE__) . DS . 'header.php'; ?>
<div id="content">
	<h2>PHPUnit is not installed!</h2>
	<p>You must install PHPUnit to use the CakePHP(tm) Test Suite.</p>
	<p>PHPUnit can either be installed with pear, using the pear installer. Or the 'PHPUnit' directory from the distribution can be placed in one of your vendors directories.</p>
	<ul>
		<li><?php echo CAKE; ?>vendors </li>
		<li><?php echo APP_DIR . DS; ?>vendors</li>
	</ul>
	<p>To install with the PEAR installer run the following commands:</p>
	<ul>
		<li>pear channel-discover pear.phpunit.de</li>
		<li>pear channel-discover components.ez.no</li>
		<li>pear channel-discover pear.symfony-project.com</li>
		<li>pear install phpunit/PHPUnit</li>
	</ul>
	<p>For full instructions on how to <a href="http://www.phpunit.de/manual/current/en/installation.html">install PHPUnit, see the PHPUnit installation guide</a>.</p>
	<p><a href="http://github.com/sebastianbergmann/phpunit" target="_blank">Download PHPUnit</a></p>
</div>
<?php include dirname(__FILE__) . DS . 'footer.php'; ?>