<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.templates.layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<p>This is regular text</p>
<!--nocache-->
	<p>A. Layout Before Content</p>
	<?php $this->log('1. layout before content') ?>
<!--/nocache-->
<!--nocache--><?php echo $this->element('nocache/plain'); ?><!--/nocache-->
<!--nocache-->
	<p>C. Layout After Test Element But Before Content</p>
	<?php $this->log('3. layout after test element but before content') ?>
<!--/nocache-->
<?php echo $content_for_layout; ?>
<!--nocache-->
	<p>E. Layout After Content</p>
	<?php $this->log('5. layout after content') ?>
<!--/nocache-->
<p>Additional regular text.</p>
<?php //echo $this->element('nocache/contains_nocache'); stub?>
<!--nocache-->
	<p>G. Layout After Content And After Element With No Cache Tags</p>
	<?php $this->log('7. layout after content and after element with no cache tags') ?>
<!--/nocache-->
