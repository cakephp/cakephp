<?php
/**
 * View Variables Panel Element
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2> <?php echo __d('debug_kit', 'Request History'); ?></h2>
<?php if (empty($content)): ?>
	<p class="warning"><?php echo __d('debug_kit', 'No previous requests logged.'); ?></p>
<?php else: ?>
	<?php echo count($content); ?> <?php echo __d('debug_kit', 'previous requests available') ?>
	<ul class="history-list">
		<li><?php echo $this->Html->link(__d('debug_kit', 'Restore to current request'),
			'#', array('class' => 'history-link', 'id' => 'history-restore-current')); ?>
		</li>
		<?php foreach ($content as $previous): ?>
			<li><?php echo $this->Html->link($previous['title'], $previous['url'], array('class' => 'history-link')); ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif;
