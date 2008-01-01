<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view.templates.scaffolds
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<div class="<?php echo $pluralVar;?> view">
<h2><?php echo sprintf(__("View %s", true), $singularHumanName);?></h2>
	<dl>
<?php
$i = 0;
foreach ($scaffoldFields as $field) {
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
	$isKey = false;
	if(!empty($associations['belongsTo'])) {
		foreach ($associations['belongsTo'] as $alias => $details) {
			if($field === $details['foreignKey']) {
				$isKey = true;
				echo "\t\t<dt{$class}>".Inflector::humanize($alias)."</dt>\n";
				echo "\t\t<dd{$class}>\n\t\t\t" . $html->link(${$singularVar}[$alias][$details['displayField']], array('controller'=> $details['controller'], 'action'=>'view', ${$singularVar}[$alias][$details['primaryKey']])) . "\n\t\t</dd>\n";
				break;
			}
		}
	}
	if($isKey !== true) {
		echo "\t\t<dt{$class}>".Inflector::humanize($field)."</dt>\n";
		echo "\t\t<dd{$class}>\n\t\t\t" . ${$singularVar}[$modelClass][$field] . " \n\t\t</dd>\n";
	}
}
?>
	</dl>
</div>
<div class="actions">
	<ul>
<?php
	echo "\t\t<li>" .$html->link(sprintf(__('Edit %s', true), $singularHumanName),   array('action'=>'edit', ${$singularVar}[$modelClass][$primaryKey])). " </li>\n";
	echo "\t\t<li>" .$html->link(sprintf(__('Delete %s', true), $singularHumanName), array('action'=>'delete', ${$singularVar}[$modelClass][$primaryKey]), null, __('Are you sure you want to delete', true).' #' . ${$singularVar}[$modelClass][$primaryKey] . '?'). " </li>\n";
	echo "\t\t<li>" .$html->link(sprintf(__('List %s', true), $pluralHumanName), array('action'=>'index')). " </li>\n";
	echo "\t\t<li>" .$html->link(sprintf(__('New %s', true), $singularHumanName), array('action'=>'add')). " </li>\n";

	$done = array();
	foreach ($associations as $type => $data) {
		foreach($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li>".$html->link(sprintf(__('List %s', true), Inflector::humanize($details['controller'])), array('controller'=> $details['controller'], 'action'=>'index'))."</li>\n";
				echo "\t\t<li>".$html->link(sprintf(__('New %s', true), Inflector::humanize(Inflector::underscore($alias))), array('controller'=> $details['controller'], 'action'=>'add'))."</li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>
<?php
if(!empty($associations['hasOne'])) :
foreach ($associations['hasOne'] as $alias => $details): ?>
<div class="related">
	<h3><?php echo sprintf(__("Related %s", true), Inflector::humanize($details['controller']));?></h3>
<?php if (!empty(${$singularVar}[$alias])):?>
	<dl>
<?php
		$i = 0;
		$otherFields = array_keys(${$singularVar}[$alias]);
		foreach ($otherFields as $field) {
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
			echo "\t\t<dt{$class}>".Inflector::humanize($field)."</dt>\n";
			echo "\t\t<dd{$class}>\n\t" .${$singularVar}[$alias][$field] ."\n&nbsp;</dd>\n";
		}
?>
	</dl>
<?php endif; ?>
	<div class="actions">
		<ul>
			<li><?php echo $html->link(sprintf(__('Edit %s', true), Inflector::humanize(Inflector::underscore($alias))), array('controller'=> $details['controller'], 'action'=>'edit', ${$singularVar}[$alias][$details['primaryKey']]))."</li>\n";?>
		</ul>
	</div>
</div>
<?php
endforeach;
endif;

if(empty($associations['hasMany'])) {
	$associations['hasMany'] = array();
}
if(empty($associations['hasAndBelongsToMany'])) {
	$associations['hasAndBelongsToMany'] = array();
}
$relations = array_merge($associations['hasMany'], $associations['hasAndBelongsToMany']);
$i = 0;
foreach ($relations as $alias => $details):
$otherSingularVar = Inflector::variable($alias);
?>
<div class="related">
	<h3><?php echo sprintf(__("Related %s", true), Inflector::humanize($details['controller']));?></h3>
<?php if (!empty(${$singularVar}[$alias])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
<?php
		$otherFields = array_keys(${$singularVar}[$alias][0]);
		foreach ($otherFields as $field) {
			echo "\t\t<th>".Inflector::humanize($field)."</th>\n";
		}
?>
		<th class="actions">Actions</th>
	</tr>
<?php
		$i = 0;
		foreach (${$singularVar}[$alias] as ${$otherSingularVar}):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		echo "\t\t<tr{$class}>\n";

			foreach ($otherFields as $field) {
				echo "\t\t\t<td>".${$otherSingularVar}[$field]."</td>\n";
			}

			echo "\t\t\t<td class=\"actions\">\n";
			echo "\t\t\t\t" . $html->link(__('View', true), array('controller'=> $details['controller'], 'action'=>'view', ${$otherSingularVar}[$details['primaryKey']])). "\n";
			echo "\t\t\t\t" . $html->link(__('Edit', true), array('controller'=> $details['controller'], 'action'=>'edit', ${$otherSingularVar}[$details['primaryKey']])). "\n";
			echo "\t\t\t\t" . $html->link(__('Delete', true), array('controller'=> $details['controller'], 'action'=>'delete', ${$otherSingularVar}[$details['primaryKey']]), null, __('Are you sure you want to delete', true).' #' . ${$otherSingularVar}[$details['primaryKey']] . '?'). "\n";
			echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";
		endforeach;
?>
	</table>
<?php endif; ?>
	<div class="actions">
		<ul>
			<li><?php echo $html->link(sprintf(__("New %s", true), Inflector::humanize(Inflector::underscore($alias))), array('controller'=> $details['controller'], 'action'=>'add'));?> </li>
		</ul>
	</div>
</div>
<?php endforeach;?>