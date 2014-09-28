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
use Cake\Utility\String;

$associations += ['BelongsTo' => [], 'HasOne' => [], 'HasMany' => [], 'BelongsToMany' => []];
$immediateAssociations = $associations['BelongsTo'] + $associations['HasOne'];
$associationFields = collection($fields)
	->map(function($field) use ($immediateAssociations) {
		foreach ($immediateAssociations as $alias => $details) {
			if ($field === $details['foreignKey']) {
				return [$field => $details];
			}
		}
	})
	->filter()
	->reduce(function($fields, $value) {
		return $fields + $value;
	}, []);

$groupedFields = collection($fields)
	//	->filter(function($field) use ($schema) {
	//		return $schema->columnType($field) !== 'binary';
	//	})
	->groupBy(function($field) use ($schema, $associationFields) {
		if (in_array($field, $schema->primaryKey())) { // Offer parent_id support for trees?
			return 'meta';
		}
		$type = $schema->columnType($field);
		if (in_array($type, ['datetime', 'timestamp']) && ($field == 'created' || $field == 'modified')) {
			return 'meta';
		}
		if (isset($associationFields[$field])) {
			return 'string';
		}
		if (in_array($type, ['date', 'datetime', 'timestamp', 'time'])) {
			return 'date';
		}
		if (in_array($type, ['integer', 'float', 'decimal', 'biginteger'])) {
			return 'number';
		}
		if ($type == 'binary') {
			return 'binary';
		}
		return in_array($type, ['text', 'boolean']) ? $type : 'string';
	})
	->toArray();
$groupedFields += ['number' => [], 'string' => [], 'boolean' => [], 'date' => [], 'text' => [], 'meta' => [], 'binary' => []];

$primaryKeyVar = "\${$singularVar}->{$primaryKey[0]}";

$dateFormat = 'YYYY-MM-dd';
$dateTimeFormat = 'YYYY-MM-dd HH:mm:ss';
$timeFormat = 'HH:mm:ss';
$g0 = 'dl'; $g1 = '<' . $g0 . '>'; $g2 = '</' . $g0 . '>'; // field group tag
$l0 = 'dt'; $l1 = '<' . $l0 . '>'; $l2 = '</' . $l0 . '>'; // field label tag
$d0 = 'dd'; $d1 = '<' . $d0 . '>'; $d2 = '</' . $d0 . '>'; // field data tag
?>
<article class="<?= $pluralVar ?> view">
	<h2><?= "<?= h(\${$singularVar}->{$displayField}) ?>" ?></h2>
	<nav class="actions">
		<ul>
			<li><?= "<?= \$this->Html->link(__('Edit'), ['action' => 'edit', {$primaryKeyVar}], ['title' => __('Edit {0}', h(\${$singularVar}->{$displayField}))]) ?>" ?></li>
			<li><?= "<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', {$primaryKeyVar}], ['title' => __('Delete {0}', h(\${$singularVar}->{$displayField})), 'confirm' => __('Are you sure you want to delete # {0}?', {$primaryKeyVar})]) ?>" ?></li>
		</ul>
	</nav>
<?php if ($groupedFields['meta']) : ?>
	<section class="meta" title="<?= __('Meta') ?>">
		<h3><?= __('Meta')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['meta'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= h(\${$singularVar}->{$field}) ?>" ?><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
	<section class="strings" title="<?= __('Strings') ?>">
		<h3><?= __('Strings')?></h3>
<?php if ($groupedFields['string']) : ?>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['string'] as $field) : ?>
<?php 		if (isset($associationFields[$field])) : $details = $associationFields[$field] ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($details['property']) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= \${$singularVar}->has('{$details['property']}') ? \$this->Html->link(\${$singularVar}->{$details['property']}->{$details['displayField']}, ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}->{$details['property']}->{$details['primaryKey'][0]}]) : '' ?>" ?><?= $d2 . PHP_EOL ?>
<?php 		else : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= h(\${$singularVar}->{$field}) ?>" ?><?= $d2 . PHP_EOL ?>
<?php 		endif ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
<?php if ($groupedFields['number']) : ?>
	<section class="numbers" title="<?= __('Numbers') ?>">
		<h3><?= __('Numbers')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['number'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= \$this->Number->format(\${$singularVar}->{$field}) ?>" ?><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
<?php if ($groupedFields['date']) : ?>
	<section class="dates" title="<?= __('Dates') ?>">
		<h3><?= __('Dates')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['date'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= \${$singularVar}->{$field} ?>" ?><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
<?php if ($groupedFields['boolean']) : ?>
	<section class="booleans" title="<?= __('Flags') ?>">
		<h3><?= __('Flags')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['boolean'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><?= "<?= \${$singularVar}->{$field} ? __('True') : __('False') ?>" ?><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
<?php if ($groupedFields['binary']) : ?>
	<section class="binary" title="<?= __('Binary Data') ?>">
		<h3><?= __('Binary Data')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['binary'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><em><?= __('Binary Data') ?></em><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
<?php if ($groupedFields['text']) : ?>
	<section class="texts" title="<?= __('Texts') ?>">
		<h3><?= __('Texts')?></h3>
		<?= $g1 . PHP_EOL ?>
<?php 	foreach ($groupedFields['text'] as $field) : ?>
			<?= $l1 ?><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?><?= $l2 . PHP_EOL ?>
				<?= $d1 ?><pre><?= "<?= h(\${$singularVar}->{$field}) ?>" ?></pre><?= $d2 . PHP_EOL ?>
<?php 	endforeach ?>
		<?= $g2 . PHP_EOL ?>
	</section>
<?php endif ?>
</article>
<?php $relationsCounter = 0 ?>
<?php $relations = $associations['HasMany'] + $associations['BelongsToMany'] ?>
<?php foreach ($relations as $alias => $details) : ?>
<?php $relationsCounter++ ?>
<?php 	if (!in_array($details['foreignKey'], $details['primaryKey'])) : ?>
<?php		$details['fields'] = array_diff($details['fields'], [$details['foreignKey']]) ?>
<?php 	endif ?>
<?php 	$relatedSingularVar = Inflector::variable($alias) ?>
<article class="related" id="related_<?= $relationsCounter ?>">
	<h3><?= "<?= __('Associated " . Inflector::humanize($details['controller']) . "') ?>" ?></h3>
<?= "<?php if (!empty(\${$singularVar}->{$details['property']})): ?>" . PHP_EOL ?>
	<style type="text/css">
<?php 	$relatedFieldCount = 0; foreach ($details['fields'] as $field) : $relatedFieldCount++ ?>
		#related_<?= $relationsCounter ?>.related tbody td:nth-of-type(<?= $relatedFieldCount ?>):before { content: '<?= Inflector::humanize($field) ?>' }
<?php 	endforeach ?>
	</style>
	<table>
		<thead>
			<tr>
<?php 	foreach ($details['fields'] as $field) : ?>
				<th><?= __(Inflector::humanize($field)) ?></th>
<?php 	endforeach ?>
				<th class="actions"><?= "<?= __('Actions') ?>" ?></th>
			</tr>
		</thead>
		<tbody>
<?= "<?php 	foreach (\${$singularVar}->{$details['property']} as \${$relatedSingularVar}): ?>" . PHP_EOL ?>
<?php /* TODO filter parent foreign key */ ?>
			<tr>
<?php	foreach ($details['fields'] as $index => $field) : ?>
				<td><?= "<?= h(\${$relatedSingularVar}->{$field}) ?>" ?></td>
<?php 	endforeach ?>
<?php 	$relatedPrimaryKey = "\${$relatedSingularVar}->{$details['primaryKey'][0]}" ?>
<?php   $relatedDisplayField = $details['displayField'] ?>
<?php 	$relatedDisplayFieldVar = "\${$relatedSingularVar}->{$relatedDisplayField}" ?>
				<td class="actions">
					<?= "<?= \$this->Html->link(__('View'), ['controller' => '{$details['controller']}', 'action' => 'view', {$relatedPrimaryKey}], ['title' => __('View {0}', {$relatedDisplayFieldVar})]) ?> " . PHP_EOL ?>
					<?= "<?= \$this->Html->link(__('Edit'), ['controller' => '{$details['controller']}', 'action' => 'edit', {$relatedPrimaryKey}], ['title' => __('Edit {0}', {$relatedDisplayFieldVar})]) ?> " . PHP_EOL ?>
					<?= "<?= \$this->Form->postLink(__('Delete'), ['controller' => '{$details['controller']}', 'action' => 'delete', {$relatedPrimaryKey}], ['title' => __('Delete {0}', {$relatedDisplayFieldVar}), 'confirm' => __('Are you sure you want to delete # {0}?', {$relatedPrimaryKey})]) ?> " . PHP_EOL ?>
				</td>
			</tr>
<?= "<?php 	endforeach ?>" . PHP_EOL ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="100%" class="actions"><?= "<?= \$this->Html->link(__('New " . Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) . "'), ['controller' => '{$details['controller']}', 'action' => 'add']) ?>" ?></td>
			</tr>
		</tfoot>
	</table>
<?= "<?php endif ?>" . PHP_EOL ?>
</article>
<?php endforeach ?>
<nav class="actions" id="actions_opened">
	<h3><a href="#actions_opened"><?= "<?= __('Actions') ?>" ?></a><a href="#actions_closed" id="actions_closed" title="<?=__('Close Actions Menu')?>">X</a></h3>
	<ul>
		<li><?= "<?= \$this->Html->link(__('List {0}', __('" . $pluralHumanName . "')), ['action' => 'index']) ?>" ?></li>
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