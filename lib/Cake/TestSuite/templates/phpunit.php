<?php
/**
 * Missing PHPUnit
 * error page.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.templates
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php include dirname(__FILE__) . DS . 'header.php'; ?>
<div id="content">
	<h2>PHPUnit is not installed!</h2>
	<p>You must install PHPUnit to use the CakePHP(tm) Test Suite.</p>
	<p>PHPUnit can be installed with pear, using the pear installer.</p>
	<p>To install with the PEAR installer run the following commands:</p>
	<ul>
		<li><code>pear config-set auto_discover 1</code></li>
		<li><code>pear install pear.phpunit.de/PHPUnit</code></li>
	</ul>
	<p>Once PHPUnit is installed make sure its located on PHP's <code>include_path</code> by checking your php.ini</p>
	<p>For full instructions on how to <a href="http://www.phpunit.de/manual/current/en/installation.html" target="_blank">install PHPUnit, see the PHPUnit installation guide</a>.</p>
	<p><a href="http://github.com/sebastianbergmann/phpunit" target="_blank">Download PHPUnit</a></p>
</div>
<?php
include dirname(__FILE__) . DS . 'footer.php';
