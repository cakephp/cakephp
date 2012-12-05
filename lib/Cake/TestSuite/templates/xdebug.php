<?php
/**
 * Xdebug error page
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.templates
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php include dirname(__FILE__) . DS . 'header.php'; ?>
<div id="content">
	<h2>Xdebug is not installed</h2>
	<p>You must install Xdebug to use the CakePHP(tm) Code Coverage Analyzation.</p>
	<p><a href="http://www.xdebug.org/docs/install" target="_blank">Learn How To Install Xdebug</a></p>
</div>
<?php
include dirname(__FILE__) . DS . 'footer.php';
