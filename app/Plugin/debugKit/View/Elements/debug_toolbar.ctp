<?php
/**
 * Debug Toolbar Element
 *
 * Renders all of the other panel elements.
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
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<div id="debug-kit-toolbar">
	<?php if (empty($debugToolbarPanels)) :?>
		<p class="warning"><?php echo __d('debug_kit', 'There are no active panels. You must enable a panel to see its output.'); ?></p>
	<?php else: ?>
		<ul id="panel-tabs">
			<li class="panel-tab icon">
				<a href="#hide" id="hide-toolbar">
					<?php echo $this->Html->image('/debug_kit/img/cake.icon.png', array('alt' => 'CakePHP')); ?>
				</a>
			</li>
		<?php foreach ($debugToolbarPanels as $panelName => $panelInfo): ?>
			<?php $panelUnderscore = Inflector::underscore($panelName);?>
			<li class="panel-tab">
			<?php
				$title = (empty($panelInfo['title'])) ? Inflector::humanize($panelUnderscore) : $panelInfo['title'];
				echo $this->Toolbar->panelStart($title, $panelUnderscore);
			?>
				<div class="panel-content" id="<?php echo $panelUnderscore ?>-tab">
					<a href="#" class="panel-toggle ui-control ui-button">+</a>
					<div class="panel-resize-region">
						<div class="panel-content-data">
							<?php
								echo $this->element($panelInfo['elementName'], $panelInfo, array(
									'plugin' => (empty($panelInfo['plugin'])) ? null : Inflector::camelize($panelInfo['plugin'])
								));
							?>
						</div>
						<div class="panel-content-data panel-history" id="<?php echo $panelUnderscore; ?>-history">
							<!-- content here -->
						</div>
					</div>
					<div class="panel-resize-handle ui-control">====</div>
				</div>
			<?php $this->Toolbar->panelEnd(); ?>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
