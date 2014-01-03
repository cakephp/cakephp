<?php
/**
 *
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2><?= __d('cake_dev', 'Missing Layout'); ?></h2>
<p class="error">
	<strong><?= __d('cake_dev', 'Error'); ?>: </strong>
	<?= __d('cake_dev', 'The layout file %s can not be found or does not exist.', '<em>' . h($file) . '</em>'); ?>
</p>
<p class="error">
	<strong><?= __d('cake_dev', 'Error'); ?>: </strong>
	<?= __d('cake_dev', 'Confirm you have created the file: %s', '<em>' . h($file) . '</em>'); ?>
</p>
<p class="notice">
	<strong><?= __d('cake_dev', 'Notice'); ?>: </strong>
	<?= __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Error' . DS . 'missing_layout.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
