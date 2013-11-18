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
 * @package       Cake.View.Scaffolds
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<div class="<?php echo $pluralVar; ?> view">
<h2><?php echo __d('cake', 'View %s', $singularHumanName); ?></h2>
	<dl>
<?php
foreach ($scaffoldFields as $_field) {
	$isKey = false;
	if (!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $_alias => $_details) {
			if ($_field === $_details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt>" . Inflector::humanize($_alias) . "</dt>\n";
				echo "\t\t<dd>\n\t\t\t";
				echo $this->Html->link(
					${$singularVar}[$_alias][$_details['displayField']],
					array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'view', ${$singularVar}[$_alias][$_details['primaryKey']])
				);
				echo "\n\t\t&nbsp;</dd>\n";
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
	echo "\t\t<li>";
	echo $this->Html->link(__d('cake', 'Edit %s', $singularHumanName), array('action' => 'edit', ${$singularVar}[$modelClass][$primaryKey]));
	echo " </li>\n";

	echo "\t\t<li>";
	echo $this->Form->postLink(__d('cake', 'Delete %s', $singularHumanName), array('action' => 'delete', ${$singularVar}[$modelClass][$primaryKey]), array(), __d('cake', 'Are you sure you want to delete # %s?', ${$singularVar}[$modelClass][$primaryKey]));
	echo " </li>\n";

	echo "\t\t<li>";
	echo $this->Html->link(__d('cake', 'List %s', $pluralHumanName), array('action' => 'index'));
	echo " </li>\n";

	echo "\t\t<li>";
	echo $this->Html->link(__d('cake', 'New %s', $singularHumanName), array('action' => 'add'));
	echo " </li>\n";

	$done = array();
	foreach ($associations as $_type => $_data) {
		foreach ($_data as $_alias => $_details) {
			if ($_details['controller'] != $this->name && !in_array($_details['controller'], $done)) {
				echo "\t\t<li>";
				echo $this->Html->link(
					__d('cake', 'List %s', Inflector::humanize($_details['controller'])),
					array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'index')
				);
				echo "</li>\n";
				echo "\t\t<li>";
				echo $this->Html->link(
					__d('cake', 'New %s', Inflector::humanize(Inflector::underscore($_alias))),
					array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'add')
				);
				echo "</li>\n";
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
<?php if (!empty(${$singularVar}[$_alias])): ?>
	<dl>
<?php
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
		<li><?php
			echo $this->Html->link(
				__d('cake', 'Edit %s', Inflector::humanize(Inflector::underscore($_alias))),
				array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'edit', ${$singularVar}[$_alias][$_details['primaryKey']])
			);
			echo "</li>\n";
			?>
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
<?php if (!empty(${$singularVar}[$_alias])): ?>
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
			echo "\t\t\t\t";
			echo $this->Html->link(
				__d('cake', 'View'),
				array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'view', ${$otherSingularVar}[$_details['primaryKey']])
			);
			echo "\n";
			echo "\t\t\t\t";
			echo $this->Html->link(
				__d('cake', 'Edit'),
				array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'edit', ${$otherSingularVar}[$_details['primaryKey']])
			);
			echo "\n";
			echo "\t\t\t\t";
			echo $this->Form->postLink(
				__d('cake', 'Delete'),
				array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'delete', ${$otherSingularVar}[$_details['primaryKey']]),
				array(),
				__d('cake', 'Are you sure you want to delete # %s?', ${$otherSingularVar}[$_details['primaryKey']])
			);
			echo "\n";
			echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";
		endforeach;
?>
	</table>
<?php endif; ?>
	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(
				__d('cake', "New %s", Inflector::humanize(Inflector::underscore($_alias))),
				array('plugin' => $_details['plugin'], 'controller' => $_details['controller'], 'action' => 'add')
			); ?> </li>
		</ul>
	</div>
</div>
<?php endforeach; ?>
