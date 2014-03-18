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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;
?>
<div class="<?= $pluralVar; ?> index">
	<h2><?= "<?= __('{$pluralHumanName}'); ?>"; ?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
	<?php foreach ($fields as $field): ?>
		<th><?= "<?= \$this->Paginator->sort('{$field}'); ?>"; ?></th>
	<?php endforeach; ?>
		<th class="actions"><?= "<?= __('Actions'); ?>"; ?></th>
	</tr>
	<?php
	echo "<?php foreach (\${$pluralVar} as \${$singularVar}): ?>\n";
	echo "\t<tr>\n";
		foreach ($fields as $field) {
			$isKey = false;
			if (!empty($associations['belongsTo'])) {
				foreach ($associations['belongsTo'] as $alias => $details) {
					if ($field === $details['foreignKey']) {
						$isKey = true;
						echo "\t\t<td>\n\t\t\t<?= \$this->Html->link(\${$singularVar}['{$alias}']['{$details['displayField']}'], ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}['{$alias}']['{$details['primaryKey']}']]); ?>\n\t\t</td>\n";
						break;
					}
				}
			}
			if ($isKey !== true) {
				echo "\t\t<td><?= h(\${$singularVar}['{$modelClass}']['{$field}']); ?>&nbsp;</td>\n";
			}
		}

		echo "\t\t<td class=\"actions\">\n";
		echo "\t\t\t<?= \$this->Html->link(__('View'), ['action' => 'view', \${$singularVar}['{$modelClass}']['{$primaryKey}']]); ?>\n";
		echo "\t\t\t<?= \$this->Html->link(__('Edit'), ['action' => 'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}']]); ?>\n";
		echo "\t\t\t<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']], null, __('Are you sure you want to delete # %s?', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

	echo "<?php endforeach; ?>\n";
	?>
	</table>
	<p>
	<?= "<?php
	echo \$this->Paginator->counter([
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	]);
	?>"; ?>
	</p>
	<div class="paging">
	<?php
		echo "<?php\n";
		echo "\t\techo \$this->Paginator->prev('< ' . __('previous'), [], null, ['class' => 'prev disabled']);\n";
		echo "\t\techo \$this->Paginator->numbers(['separator' => '']);\n";
		echo "\t\techo \$this->Paginator->next(__('next') . ' >', [], null, ['class' => 'next disabled']);\n";
		echo "\t?>\n";
	?>
	</div>
</div>
<div class="actions">
	<h3><?= "<?= __('Actions'); ?>"; ?></h3>
	<ul>
		<li><?= "<?= \$this->Html->link(__('New " . $singularHumanName . "'), ['action' => 'add']); ?>"; ?></li>
<?php
	$done = [];
	foreach ($associations as $type => $data) {
		foreach ($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?= \$this->Html->link(__('List " . Inflector::humanize($details['controller']) . "'), ['controller' => '{$details['controller']}', 'action' => 'index']); ?> </li>\n";
				echo "\t\t<li><?= \$this->Html->link(__('New " . Inflector::humanize(Inflector::underscore($alias)) . "'), ['controller' => '{$details['controller']}', 'action' => 'add']); ?> </li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>
