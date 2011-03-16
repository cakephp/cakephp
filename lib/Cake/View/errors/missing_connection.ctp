<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.view.templates.errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<h2><?php echo __('Missing Database Connection'); ?></h2>
<p class="error">
	<strong><?php echo __('Error'); ?>: </strong>
	<?php echo __('%s requires a database connection', $class); ?>
</p>
<p class="error">
	<strong><?php echo __('Error'); ?>: </strong>
	<?php echo __('Confirm you have created the file : %s.', APP_DIR . DS . 'config' . DS . 'database.php'); ?>
</p>
<p class="notice">
	<strong><?php echo __('Notice'); ?>: </strong>
	<?php echo __('If you want to customize this error message, create %s.', APP_DIR . DS . 'views' . DS . 'errors' . DS . basename(__FILE__)); ?>
</p>

<?php echo $this->element('exception_stack_trace'); ?>