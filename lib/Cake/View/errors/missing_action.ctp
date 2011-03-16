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
<h2><?php echo __('Missing Method in %s', $controller); ?></h2>
<p class="error">
	<strong><?php echo __('Error'); ?>: </strong>
	<?php echo __('The action %1$s is not defined in controller %2$s', '<em>' . $action . '</em>', '<em>' . $controller . '</em>'); ?>
</p>
<p class="error">
	<strong><?php echo __('Error'); ?>: </strong>
	<?php echo __('Create %1$s%2$s in file: %3$s.', '<em>' . $controller . '::</em>', '<em>' . $action . '()</em>', APP_DIR . DS . 'controllers' . DS . Inflector::underscore($controller) . '.php'); ?>
</p>
<pre>
&lt;?php
class <?php echo $controller;?> extends AppController {

<strong>
	function <?php echo $action;?> {

	}
</strong>
}
?&gt;
</pre>
<p class="notice">
	<strong><?php echo __('Notice'); ?>: </strong>
	<?php echo __('If you want to customize this error message, create %s', APP_DIR . DS . 'views' . DS . 'errors' . DS . 'missing_action.ctp'); ?>
</p>
<?php echo $this->element('exception_stack_trace'); ?>
