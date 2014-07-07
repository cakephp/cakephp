<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2>Missing Layout</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('The layout file <em>%s</em> can not be found or does not exist.', h($file)); ?>
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
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_layout.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
