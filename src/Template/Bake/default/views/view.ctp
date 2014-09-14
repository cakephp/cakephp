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

$groupedFields = collection($fields)
	->groupBy(function($field) use ($schema) {
		$type = $schema->columnType($field);
		if (in_array($type, ['integer', 'float', 'decimal', 'biginteger'])) {
			return 'number';
		}
		if (in_array($type, ['date', 'time', 'datetime', 'timestamp'])) {
			return 'date';
		}
		return in_array($type, ['text', 'boolean']) ? $type : 'string';
	})->toArray();

	$groupedFields += ['number' => [], 'string' => [], 'boolean' => [], 'date' => []];
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
		<div class="large-6 columns strings">
<?php foreach ($groupedFields['string'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= h(\${$singularVar}->{$field}) ?>" ?></p>
<?php endforeach; ?>
		</div>
		<div class="large-2 columns numbers">
<?php foreach ($groupedFields['number'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= \$this->Number->format(\${$singularVar}->{$field}) ?>" ?></p>
<?php endforeach; ?>
		</div>
		<div class="large-2 columns dates">
<?php foreach ($groupedFields['date'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= h(\${$singularVar}->{$field}->niceShort()) ?>" ?></p>
<?php endforeach; ?>
		</div>
		<div class="large-2 columns booleans">
<?php foreach ($groupedFields['boolean'] as $field) : ?>
			<h6 class="subheader"><?= "<?= __('" . Inflector::humanize($field) . "') ?>" ?></h6>
			<p><?= "<?= \${$singularVar}->{$field} ? __('Yes') : __('No'); ?>" ?></p>
<?php endforeach; ?>
		</div>
	</div>
	<dl>
<?php
foreach ($fields as $field) {
	$isKey = false;
	if (!empty($associations['BelongsTo'])) {
		foreach ($associations['BelongsTo'] as $alias => $details) {
			if ($field === $details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt><?= __('" . Inflector::humanize(Inflector::underscore($details['property'])) . "') ?></dt>\n";
				echo "\t\t<dd>\n\t\t\t<?= \${$singularVar}->has('{$details['property']}') ? \$this->Html->link(\${$singularVar}->{$details['property']}->{$details['displayField']}, ['controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}->{$details['property']}->{$details['primaryKey'][0]}]) : '' ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
				break;
			}
		}
	}
	if ($isKey !== true) {
		echo "\t\t<dt><?= __('" . Inflector::humanize($field) . "') ?></dt>\n";
		echo "\t\t<dd>\n\t\t\t<?= h(\${$singularVar}->{$field}) ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
	}
}
?>
	</dl>
</div>
<?php
if (!empty($associations['HasOne'])) :
	foreach ($associations['HasOne'] as $alias => $details): ?>
	<div class="related">
		<h3 class="subheader"><?= "<?= __('Related " . Inflector::humanize($details['controller']) . "') ?>"; ?></h3>
	<?= "<?php if (!empty(\${$singularVar}['{$alias}'])): ?>\n"; ?>
		<dl>
	<?php
			foreach ($details['fields'] as $field) {
				echo "\t\t<dt><?= __('" . Inflector::humanize($field) . "') ?></dt>\n";
				echo "\t\t<dd>\n\t<?= h(\${$singularVar}->{$details['property']}->{$field}) ?>\n&nbsp;</dd>\n";
			}
	?>
		</dl>
	<?= "<?php endif; ?>\n"; ?>
		<div class="actions">
			<ul>
				<li><?= "<?= \$this->Html->link(__('Edit " . Inflector::humanize(Inflector::underscore($alias)) . "'), ['controller' => '{$details['controller']}', 'action' => 'edit', \${$singularVar}->{$details['property']}->{$details['primaryKey'][0]}]) ?></li>\n"; ?>
			</ul>
		</div>
	</div>
	<?php
	endforeach;
endif;

if (empty($associations['HasMany'])) {
	$associations['HasMany'] = [];
}
if (empty($associations['BelongsToMany'])) {
	$associations['BelongsToMany'] = [];
}

$relations = array_merge($associations['HasMany'], $associations['BelongsToMany']);
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
