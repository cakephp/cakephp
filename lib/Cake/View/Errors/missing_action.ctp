<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<h2><?php echo __d('cake_dev', 'Missing Method in %s', $controller); ?></h2> <p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', 'The action %1$s is not defined in controller %2$s', '<em>' . $action . '</em>', '<em>' . $controller . '</em>'); ?>
</p>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', 'Create %1$s%2$s in file: %3$s.', '<em>' . $controller . '::</em>', '<em>' . $action . '()</em>', APP_DIR . DS . 'Controller' . DS . $controller . '.php'); ?>
</p>
<pre>
&lt;?php
class <?php echo $controller;?> extends AppController {

<strong>
	public function <?php echo $action;?>() {

	}
</strong>
}
</pre>
<p class="notice">
	<strong><?php echo __d('cake_dev', 'Notice'); ?>: </strong>
	<?php echo __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Errors' . DS . 'missing_action.ctp'); ?>
</p>
<?php echo $this->element('exception_stack_trace'); ?>
