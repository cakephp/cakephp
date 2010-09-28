<?php
/**
 * Missing Connection error page
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
?>
<?php include dirname(__FILE__) . DS . 'header.php'; ?>
<div id="content">
	<h2>Missing Test Database Connection</h2>
	<h3><?php echo $exception->getMessage(); ?></h3>
	<pre><?php echo $exception->getTraceAsString(); ?></pre>
</div>
<?php include dirname(__FILE__) . DS . 'footer.php'; ?>