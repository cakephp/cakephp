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
			if (!empty($associations['BelongsTo'])) {
				foreach ($associations['BelongsTo'] as $alias => $details) {
					if ($field === $details['foreignKey']) {
						$isKey = true;
						echo "\t\t<td>\n\t\t\t<?= \$this->Html->link(\${$singularVar}->{$details['property']}->{$details['displayField']}, ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}->{$details['primaryKey'][0]}]); ?>\n\t\t</td>\n";
						break;
					}
				}
			}
			if ($isKey !== true) {
				echo "\t\t<td><?= h(\${$singularVar}->{$field}); ?>&nbsp;</td>\n";
			}
		}

		$pk = "\${$singularVar}->{$primaryKey[0]}";

		echo "\t\t<td class=\"actions\">\n";
		echo "\t\t\t<?= \$this->Html->link(__('View'), ['action' => 'view', {$pk}]); ?>\n";
		echo "\t\t\t<?= \$this->Html->link(__('Edit'), ['action' => 'edit', {$pk}]); ?>\n";
		echo "\t\t\t<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', {$pk}], [], __('Are you sure you want to delete # %s?', {$pk})); ?>\n";
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

	echo "\t<?php endforeach; ?>\n";
	?>
	</table>
	<p><?= "<?= \$this->Paginator->counter(); ?>"; ?></p>
	<div class="paging">
	<?php
		echo "<?php\n";
		echo "\t\techo \$this->Paginator->prev('< ' . __('previous'));\n";
		echo "\t\techo \$this->Paginator->numbers();\n";
		echo "\t\techo \$this->Paginator->next(__('next') . ' >');\n";
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
