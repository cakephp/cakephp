<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Scaffolds
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<div class="<?php echo $pluralVar;?> view">
<h2><?php echo __d('cake', 'View %s', $singularHumanName); ?></h2>
	<dl>
<?php
$i = 0;
foreach ($scaffoldFields as $_field) {
	$isKey = false;
	if (!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $_alias => $_details) {
			if ($_field === $_details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt>" . Inflector::humanize($_alias) . "</dt>\n";
				echo "\t\t<dd>\n\t\t\t" . $this->Html->link(${$singularVar}[$_alias][$_details['displayField']], array('controller' => $_details['controller'], 'action' => 'view', ${$singularVar}[$_alias][$_details['primaryKey']])) . "\n\t\t&nbsp;</dd>\n";
				break;
			}
		}
	}
	if ($isKey !== true) {
		echo "\t\t<dt>" . Inflector::humanize($_field) . "</dt>\n";
		echo "\t\t<dd>" . h(${$singularVar}[$modelClass][$_field]) . "&nbsp;</dd>\n";
	}
}
?>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __d('cake', 'Actions'); ?></h3>
	<ul>
<?php
	echo "\t\t<li>" . $this->Html->link(__d('cake', 'Edit %s', $singularHumanName),   array('action' => 'edit', ${$singularVar}[$modelClass][$primaryKey])) . " </li>\n";
	echo "\t\t<li>" . $this->Html->link(__d('cake', 'Delete %s', $singularHumanName), array('action' => 'delete', ${$singularVar}[$modelClass][$primaryKey]), null, __d('cake', 'Are you sure you want to delete').' #' . ${$singularVar}[$modelClass][$primaryKey] . '?') . " </li>\n";
	echo "\t\t<li>" . $this->Html->link(__d('cake', 'List %s', $pluralHumanName), array('action' => 'index')) . " </li>\n";
	echo "\t\t<li>" . $this->Html->link(__d('cake', 'New %s', $singularHumanName), array('action' => 'add')) . " </li>\n";

	$done = array();
	foreach ($associations as $_type => $_data) {
		foreach ($_data as $_alias => $_details) {
			if ($_details['controller'] != $this->name && !in_array($_details['controller'], $done)) {
				echo "\t\t<li>" . $this->Html->link(__d('cake', 'List %s', Inflector::humanize($_details['controller'])), array('controller' => $_details['controller'], 'action' => 'index')) . "</li>\n";
				echo "\t\t<li>" . $this->Html->link(__d('cake', 'New %s', Inflector::humanize(Inflector::underscore($_alias))), array('controller' => $_details['controller'], 'action' => 'add')) . "</li>\n";
				$done[] = $_details['controller'];
			}
		}
	}
?>
	</ul>
</div>
<?php
if (!empty($associations['hasOne'])) :
foreach ($associations['hasOne'] as $_alias => $_details): ?>
<div class="related">
	<h3><?php echo __d('cake', "Related %s", Inflector::humanize($_details['controller'])); ?></h3>
<?php if (!empty(${$singularVar}[$_alias])):?>
	<dl>
<?php
		$i = 0;
		$otherFields = array_keys(${$singularVar}[$_alias]);
		foreach ($otherFields as $_field) {
			echo "\t\t<dt>" . Inflector::humanize($_field) . "</dt>\n";
			echo "\t\t<dd>\n\t" . ${$singularVar}[$_alias][$_field] . "\n&nbsp;</dd>\n";
		}
?>
	</dl>
<?php endif; ?>
	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(__d('cake', 'Edit %s', Inflector::humanize(Inflector::underscore($_alias))), array('controller' => $_details['controller'], 'action' => 'edit', ${$singularVar}[$_alias][$_details['primaryKey']]))."</li>\n";?>
		</ul>
	</div>
</div>
<?php
endforeach;
endif;

if (empty($associations['hasMany'])) {
	$associations['hasMany'] = array();
}
if (empty($associations['hasAndBelongsToMany'])) {
	$associations['hasAndBelongsToMany'] = array();
}
$relations = array_merge($associations['hasMany'], $associations['hasAndBelongsToMany']);
$i = 0;
foreach ($relations as $_alias => $_details):
$otherSingularVar = Inflector::variable($_alias);
?>
<div class="related">
	<h3><?php echo __d('cake', "Related %s", Inflector::humanize($_details['controller'])); ?></h3>
<?php if (!empty(${$singularVar}[$_alias])):?>
	<table cellpadding="0" cellspacing="0">
	<tr>
<?php
		$otherFields = array_keys(${$singularVar}[$_alias][0]);
		if (isset($_details['with'])) {
			$index = array_search($_details['with'], $otherFields);
			unset($otherFields[$index]);
		}
		foreach ($otherFields as $_field) {
			echo "\t\t<th>" . Inflector::humanize($_field) . "</th>\n";
		}
?>
		<th class="actions">Actions</th>
	</tr>
<?php
		$i = 0;
		foreach (${$singularVar}[$_alias] as ${$otherSingularVar}):
			echo "\t\t<tr>\n";

			foreach ($otherFields as $_field) {
				echo "\t\t\t<td>" . ${$otherSingularVar}[$_field] . "</td>\n";
			}

			echo "\t\t\t<td class=\"actions\">\n";
			echo "\t\t\t\t" . $this->Html->link(__d('cake', 'View'), array('controller' => $_details['controller'], 'action' => 'view', ${$otherSingularVar}[$_details['primaryKey']])). "\n";
			echo "\t\t\t\t" . $this->Html->link(__d('cake', 'Edit'), array('controller' => $_details['controller'], 'action' => 'edit', ${$otherSingularVar}[$_details['primaryKey']])). "\n";
			echo "\t\t\t\t" . $this->Html->link(__d('cake', 'Delete'), array('controller' => $_details['controller'], 'action' => 'delete', ${$otherSingularVar}[$_details['primaryKey']]), null, __d('cake', 'Are you sure you want to delete', true).' #' . ${$otherSingularVar}[$_details['primaryKey']] . '?'). "\n";
			echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";
		endforeach;
?>
	</table>
<?php endif; ?>
	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(__d('cake', "New %s", Inflector::humanize(Inflector::underscore($_alias))), array('controller' => $_details['controller'], 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
<?php endforeach;?>
