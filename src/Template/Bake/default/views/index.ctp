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

$fields = collection($fields)
	->filter(function($field) use ($schema) {
		return !in_array($schema->columnType($field), ['binary', 'text']);
	});
	// ->take(7);
$fieldCount = 0;
$primaryKeyVar = "\${$singularVar}->{$primaryKey[0]}";
$displayFieldVar = "\${$singularVar}->{$displayField}";
?>
<div class="<?= $pluralVar ?> index">
	<style type="text/css">
<?php foreach ($fields as $field): $fieldCount++ ?>
		.index tbody td:nth-of-type(<?= $fieldCount ?>):before { content: '<?= Inflector::humanize($field) ?>' }
<?php endforeach ?>
	</style>
	<table>
		<thead>
			<tr>
<?php foreach ($fields as $field): ?>
				<th><?= "<?= \$this->Paginator->sort('{$field}') ?>" ?></th>
<?php endforeach ?>
				<th><?= "<?= __('Actions') ?>" ?></th>
			</tr>
		</thead>
		<tbody>
<?= "<?php foreach (\${$pluralVar} as \${$singularVar}): ?>" . PHP_EOL ?>
			<tr>
<?php foreach ($fields as $field) : ?>
<?php	$isAssociation = false ?>
<?php 	if (!empty($associations['BelongsTo'])) : ?>
<?php		foreach ($associations['BelongsTo'] as $alias => $details) : ?>
<?php			if ($field === $details['foreignKey']) : ?>
<?php 				$isAssociation = true ?>
				<td><?= "<?= \${$singularVar}->has('{$details['property']}') ? \$this->Html->link(\${$singularVar}->{$details['property']}->{$details['displayField']}, ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}->{$details['property']}->{$details['primaryKey'][0]}]) : '' ?>" ?></td>
<?php				break ?>
<?php			endif ?>
<?php		endforeach ?>
<?php	endif ?>
<?php	if ($isAssociation == false) : ?>
<?php		if (!in_array($field, $schema->primaryKey()) && in_array($schema->columnType($field), ['integer', 'biginteger', 'decimal', 'float'])) : ?>
				<td><?= "<?= \$this->Number->format(\${$singularVar}->{$field}) ?>" ?></td>
<?php		else : ?>
				<td><?= "<?= h(\${$singularVar}->{$field}) ?>" ?></td>
<?php		endif ?>
<?php	endif ?>
<?php endforeach ?>
				<td class="actions">
					<?= "<?= \$this->Html->link(__('View'), ['action' => 'view', {$primaryKeyVar}], ['title' => __('View {0}', {$displayFieldVar})]) ?> " . PHP_EOL ?>
					<?= "<?= \$this->Html->link(__('Edit'), ['action' => 'edit', {$primaryKeyVar}], ['title' => __('Edit {0}', {$displayFieldVar})]) ?> " . PHP_EOL ?>
					<?= "<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', {$primaryKeyVar}], ['title' => __('Delete {0}', {$displayFieldVar}), 'confirm' => __('Are you sure you want to delete # {0}?', {$primaryKeyVar})]) ?> " . PHP_EOL ?>
				</td>
			</tr>
<?= "<?php endforeach ?>" . PHP_EOL ?>
		</tbody>
		<tfoot>
			<tr class="pagination">
				<td colspan="100%">
					<nav>
						<h3>Paginator</h3>
						<ul>
							<?= "<?= \$this->Paginator->prev('◄ ' . __('Prev')) ?> " . PHP_EOL ?>
							<?= "<?= \$this->Paginator->numbers() ?> " . PHP_EOL ?>
							<?= "<?= \$this->Paginator->next(__('Next') . ' ►') ?> " . PHP_EOL ?>
						</ul>
					</nav>
					<p><?= "<?= \$this->Paginator->counter() ?>" ?></p>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<nav class="actions" id="actions_opened">
	<h3><a href="#actions_opened"><?= "<?= __('Actions') ?>" ?></a><a href="#actions_closed" id="actions_closed" title="<?=__('Close Actions Menu')?>">X</a></h3>
	<ul>
		<li class="separator"><?= "<?= \$this->Html->link(__('New {0}', __('" . $singularHumanName . "')), ['action' => 'add']) ?>" ?></li>
<?php $processedAssociations = [] ?>
<?php foreach ($associations as $type => $data) : ?>
<?php 	foreach ($data as $alias => $details) : ?>
<?php 		if ($details['controller'] != $this->name && !in_array($details['variable'], $processedAssociations)) : ?>
		<li><?= "<?= \$this->Html->link(__('List {0}', __('" . Inflector::humanize(Inflector::pluralize($details['variable'])) . "')), ['controller' => '{$details['controller']}', 'action' => 'index']) ?>" ?></li>
		<li class="separator"><?= "<?= \$this->Html->link(__('New {0}', __('" . Inflector::humanize(Inflector::singularize($details['variable'])) . "')), ['controller' => '{$details['controller']}', 'action' => 'add']) ?>" ?></li>
<?php			$processedAssociations[] = $details['variable'] ?>
<?php 		endif ?>
<?php 	endforeach ?>
<?php endforeach ?>
	</ul>
</nav>