<?php
/**
 * Missing Connection error page
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
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<?php include __DIR__ . DS . 'header.php'; ?>
<div id="content">
	<h2>Missing Test Database Connection</h2>
	<h3><?= $exception->getMessage(); ?></h3>
	<pre><?= $exception->getTraceAsString(); ?></pre>
</div>
<?php
include __DIR__ . DS . 'footer.php';
