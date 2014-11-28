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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<div class="columns large-10 large-push-2">
	<h2><?= $this->fetch('heading') ?></h2>
	<p class="error">
		<strong>Error: </strong>
		<?= $this->fetch('subheading') ?>
	</p>
	<div class="error-suggestion">
		<?= $this->fetch('file') ?>
	</div>
	<p class="notice">
		<strong>Notice: </strong>
		<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . $this->fetch('templateName')); ?>
	</p>
</div>
<?= $this->element('exception_stack_trace'); ?>
