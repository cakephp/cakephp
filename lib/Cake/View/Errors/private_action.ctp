<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2><?php echo __d('cake_dev', 'Private Method in %s', $controller); ?></h2>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', '%s%s cannot be accessed directly.', '<em>' . h($controller) . '::</em>', '<em>' . h($action) . '()</em>'); ?>
</p>
<p class="notice">
	<strong><?php echo __d('cake_dev', 'Notice'); ?>: </strong>
	<?php echo __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Errors' . DS . 'private_action.ctp'); ?>
</p>

<?php
echo $this->element('exception_stack_trace');
?>
