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
	->groupBy(function($field) use ($schema, $associationFields) {
		$type = $schema->columnType($field);
		if (isset($associationFields[$field])) {
			return 'string';
		}
		if (in_array($type, ['integer', 'float', 'decimal', 'biginteger'])) {
			return 'number';
		}
		if (in_array($type, ['date', 'time', 'datetime', 'timestamp'])) {
			return 'date';
		}
		return in_array($type, ['text', 'boolean']) ? $type : 'string';
	})
	->toArray();

$groupedFields += ['number' => [], 'string' => [], 'boolean' => [], 'date' => [], 'text' => []];
?>
<div class="actions columns large-2 medium-3">
	<h3><?= "<?= __('Actions'); ?>"; ?></h3>
	<ul class="side-nav">
<?php
	$pk = "\${$singularVar}->{$primaryKey[0]}";

	echo "\t\t<li><?= \$this->Html->link(__('Edit " . $singularHumanName ."'), ['action' => 'edit', {$pk}]) ?> </li>\n";
	echo "\t\t<li><?= \$this->Form->postLink(__('Delete " . $singularHumanName . "'), ['action' => 'delete', {$pk}], ['confirm' => __('Are you sure you want to delete # %s?', {$pk})]) ?> </li>\n";
	echo "\t\t<li><?= \$this->Html->link(__('List " . $pluralHumanName . "'), ['action' => 'index']) ?> </li>\n";
	echo "\t\t<li><?= \$this->Html->link(__('New " . $singularHumanName . "'), ['action' => 'add']) ?> </li>\n";

	$done = [];
	foreach ($associations as $type => $data) {
		foreach ($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?= \$this->Html->link(__('List " . Inflector::humanize($details['controller']) . "'), ['controller' => '{$details['controller']}', 'action' => 'index']) ?> </li>\n";
				echo "\t\t<li><?= \$this->Html->link(__('New " . Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) . "'), ['controller' => '{$details['controller']}', 'action' => 'add']) ?> </li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>
<div class="<?= $pluralVar ?> view large-10 medium-9 columns">
	<h2><?= "<?= h(\${$singularVar}->{$displayField}) ?>"; ?></h2>
	<div class="row">
<?php if ($groupedFields['string']) : ?>
		<div class="large-5 columns strings">
<?php foreach ($groupedFields['string'] as $field) : ?>
<?php if (isset($associationFields[$field])) :
			$details = $associationFields[$field];
?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($details['property']) . "') ?>" ?></h6>
			<p><?= "<?= \${$singularVar}->has('{$details['property']}') ? \$this->Html->link(\${$singularVar}->{$details['property']}->{$details['displayField']}, ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}->{$details['property']}->{$details['primaryKey'][0]}]) : '' ?>" ?></p>
<?php else : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= h(\${$singularVar}->{$field}) ?>" ?></p>
<?php endif; ?>
<?php endforeach; ?>
		</div>
<?php endif; ?>
<?php if ($groupedFields['number']) : ?>
		<div class="large-2 larege-offset-1 columns numbers end">
<?php foreach ($groupedFields['number'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= \$this->Number->format(\${$singularVar}->{$field}) ?>" ?></p>
<?php endforeach; ?>
		</div>
<?php endif; ?>
<?php if ($groupedFields['date']) : ?>
		<div class="large-2 columns dates end">
<?php foreach ($groupedFields['date'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= h(\${$singularVar}->{$field}) ?>" ?></p>
<?php endforeach; ?>
		</div>
<?php endif; ?>
<?php if ($groupedFields['boolean']) : ?>
		<div class="large-2 columns booleans end">
<?php foreach ($groupedFields['boolean'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= \${$singularVar}->{$field} ? __('Yes') : __('No'); ?>" ?></p>
<?php endforeach; ?>
		</div>
<?php endif; ?>
	</div>
<?php if ($groupedFields['text']) : ?>
<?php foreach ($groupedFields['text'] as $field) : ?>
	<div class="row">
		<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
		<?= "<?= \$this->Text->autoParagraph(h(\${$singularVar}->{$field})); ?>" ?>
	</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php
$relations = $associations['HasMany'] + $associations['BelongsToMany'];
foreach ($relations as $alias => $details):
	$otherSingularVar = Inflector::variable($alias);
	$otherPluralHumanName = Inflector::humanize($details['controller']);
	?>
<div class="related">
	<h3 class="subheader"><?= "<?= __('Related " . $otherPluralHumanName . "') ?>"; ?></h3>
	<?= "<?php if (!empty(\${$singularVar}->{$details['property']})): ?>\n"; ?>
	<table cellpadding="0" cellspacing="0">
		<tr>
<?php
			foreach ($details['fields'] as $field) {
				echo "\t\t\t<th><?= __('" . Inflector::humanize($field) . "') ?></th>\n";
			}
?>
			<th class="actions"><?= "<?= __('Actions') ?>"; ?></th>
		</tr>
<?php
echo "\t\t<?php foreach (\${$singularVar}->{$details['property']} as \${$otherSingularVar}): ?>\n";
		echo "\t\t<tr>\n";
			foreach ($details['fields'] as $field) {
				echo "\t\t\t<td><?= h(\${$otherSingularVar}->{$field}) ?></td>\n";
			}

			$otherPk = "\${$otherSingularVar}->{$details['primaryKey'][0]}";

			echo "\t\t\t<td class=\"actions\">\n";
			echo "\t\t\t\t<?= \$this->Html->link(__('View'), ['controller' => '{$details['controller']}', 'action' => 'view', {$otherPk}]) ?>\n";
			echo "\t\t\t\t<?= \$this->Html->link(__('Edit'), ['controller' => '{$details['controller']}', 'action' => 'edit', {$otherPk}]) ?>\n";
			echo "\t\t\t\t<?= \$this->Form->postLink(__('Delete'), ['controller' => '{$details['controller']}', 'action' => 'delete', {$otherPk}], ['confirm' => __('Are you sure you want to delete # %s?', {$otherPk})]) ?>\n";
			echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";

echo "\t\t<?php endforeach; ?>\n";
?>
	</table>
<?= "\t<?php endif; ?>\n"; ?>
	<div class="actions">
		<ul>
			<li><?= "<?= \$this->Html->link(__('New " . Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) . "'), ['controller' => '{$details['controller']}', 'action' => 'add']) ?>"; ?> </li>
		</ul>
	</div>
</div>
<?php endforeach; ?>
