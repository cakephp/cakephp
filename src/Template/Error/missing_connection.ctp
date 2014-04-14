<?php
/**
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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2>Missing Database Connection</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('A Database connection using "%s" was missing or unable to connect.', h($class)); ?>
	<br />
	<?php
	if (isset($message)):
		echo sprintf('The database server returned this error: %s', h($message));
	endif;
	?>
</p>
<?php if (!$enabled) : ?>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('%s driver is NOT enabled', h($class)); ?>
</p>
<?php endif; ?>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . basename(__FILE__)); ?>
</p>

<?php
echo $this->element('exception_stack_trace');
