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
<h2><?php echo __d('cake_dev', 'Missing Layout'); ?></h2>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', 'The layout file %s can not be found or does not exist.', '<em>' . h($file) . '</em>'); ?>
</p>

<p>
	<?php echo __d('cake_dev', 'Confirm you have created the file: %s', h($file)); ?>
	in one of the following paths:
</p>
<ul>
<?php
	$paths = $this->_paths($this->plugin);
	foreach ($paths as $path):
		if (strpos($path, CORE_PATH) !== false) {
			continue;
		}
		echo sprintf('<li>%s%s</li>', h($path), h($file));
	endforeach;
?>
</ul>

<p class="notice">
	<strong><?php echo __d('cake_dev', 'Notice'); ?>: </strong>
	<?php echo __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Errors' . DS . 'missing_layout.ctp'); ?>
</p>

<?php
echo $this->element('exception_stack_trace');
?>
