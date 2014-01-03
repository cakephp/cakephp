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
use Cake\Utility\Debugger;
?>
<h2><?= __d('cake_dev', 'Database Error'); ?></h2>
<p class="error">
	<strong><?= __d('cake_dev', 'Error'); ?>: </strong>
	<?= $name; ?>
</p>
<?php if (!empty($error->queryString)) : ?>
	<p class="notice">
		<strong><?= __d('cake_dev', 'SQL Query'); ?>: </strong>
		<?= h($error->queryString); ?>
	</p>
<?php endif; ?>
<?php if (!empty($error->params)) : ?>
		<strong><?= __d('cake_dev', 'SQL Query Params'); ?>: </strong>
		<?= Debugger::dump($error->params); ?>
<?php endif; ?>
<p class="notice">
	<strong><?= __d('cake_dev', 'Notice'); ?>: </strong>
	<?= __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Error' . DS . 'pdo_error.ctp'); ?>
</p>
<?= $this->element('exception_stack_trace'); ?>
