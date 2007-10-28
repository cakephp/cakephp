<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.console.libs.templates.views
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<div class="<?php echo $pluralVar;?>">
<h2><?php echo $pluralHumanName;?></h2>
<p><?php
echo $paginator->counter(array(
'format' => 'Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%'
));
?></p>
<table cellpadding="0" cellspacing="0">
<tr>
<?php foreach ($fields as $field):?>
	<th><?php echo $paginator->sort("{$field}");?></th>
<?php endforeach;?>
	<th><?php __('Actions');?></th>
</tr>
<?php
$i = 0;
foreach (${$pluralVar} as ${$singularVar}):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
echo "\n";
	echo "\t<tr" . $class . ">\n";
		foreach ($fields as $field) {
			if (in_array($field, array_keys($foreignKeys))) {
				$otherModelClass = $foreignKeys[$field][1];
				$otherModelKey = Inflector::underscore($otherModelClass);
				$otherControllerName = Inflector::pluralize($otherModelClass);
				$otherControllerPath = Inflector::underscore($otherControllerName);
				if (isset($foreignKeys[$field][2])) {
					$otherModelClass = $foreignKeys[$field][2];
				}
				$otherVariableName = Inflector::variable($otherModelClass);
				$otherModelObj =& ClassRegistry::getObject($otherModelKey);
				$otherPrimaryKey = $otherModelObj->primaryKey;
				$otherDisplayField = $otherModelObj->displayField;
				echo "\t\t<td>\n\t\t\t" . $html->link(${$singularVar}[$otherModelClass][$otherDisplayField], array('controller'=> $otherControllerPath, 'action'=>'view', ${$singularVar}[$otherModelClass][$otherPrimaryKey])) . "\n\t\t</td>\n";
			} else {
				echo "\t\t<td>\n\t\t\t" . ${$singularVar}[$modelClass][$field] . " \n\t\t</td>\n";
			}
		}

		echo "\t\t<td class=\"actions\">\n";
		echo "\t\t\t" . $html->link(__('View', true), array('action'=>'view', ${$singularVar}[$modelClass][$primaryKey])) . "\n";
	 	echo "\t\t\t" . $html->link(__('Edit', true), array('action'=>'edit', ${$singularVar}[$modelClass][$primaryKey])) . "\n";
	 	echo "\t\t\t" . $html->link(__('Delete', true), array('action'=>'delete', ${$singularVar}[$modelClass][$primaryKey]), null, __('Are you sure you want to delete', true).' #' . ${$singularVar}[$modelClass][$primaryKey]) . "\n";
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

endforeach;
echo "\n";
?>
</table>
</div>
<div class="paging">
<?php echo "\t" . $paginator->prev('<< ' . __('previous', true), array(), null, array('class'=>'disabled')) . "\n";?>
 | <?php echo $paginator->numbers() . "\n"?>
<?php echo "\t ". $paginator->next(__('next', true) .' >>', array(), null, array('class'=>'disabled')) . "\n";?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link('New '.$singularHumanName, array('action'=>'add')); ?></li>
<?php
		foreach ($foreignKeys as $field => $value) {
			$otherModelClass = $value['1'];
			if ($otherModelClass != $modelClass) {
				$otherModelKey = Inflector::underscore($otherModelClass);
				$otherControllerName = Inflector::pluralize($otherModelClass);
				$otherControllerPath = Inflector::underscore($otherControllerName);
				$otherVariableName = Inflector::variable($otherModelClass);
				$otherPluralHumanName = Inflector::humanize($otherControllerPath);
				$otherSingularHumanName = Inflector::humanize($otherModelKey);
				echo "\t\t<li>" . $html->link(__('List', true) .' '.$otherPluralHumanName, array('controller'=> $otherControllerPath, 'action'=>'index')) . "</li>\n";
				echo "\t\t<li>" . $html->link(__('New', true) .' '.$otherSingularHumanName, array('controller'=> $otherControllerPath, 'action'=>'add')) . "</li>\n";
			}
		}
?>
	</ul>
</div>