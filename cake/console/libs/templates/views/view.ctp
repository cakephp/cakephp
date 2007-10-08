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
 * @since			CakePHP(tm) v 1.2.0.5234
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<div class="<?php echo $singularVar;?>">
<h2><?php echo "<?php  __('{$singularHumanName}');?>";?></h2>
	<dl>
<?php
$i = 0;
foreach ($fields as $field) {
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}

	if (in_array($field['name'], array_keys($foreignKeys))) {
		$otherModelClass = $foreignKeys[$field['name']][1];
		$otherModelKey = Inflector::underscore($otherModelClass);
		$otherControllerName = Inflector::pluralize($otherModelClass);
		$otherControllerPath = Inflector::underscore($otherControllerName);
		if (isset($foreignKeys[$field['name']][2])) {
			$otherModelClass = $foreignKeys[$field['name']][2];
		}
		$otherSingularVar = Inflector::variable($otherModelClass);
		$otherModelObj =& ClassRegistry::getObject($otherModelKey);
		$otherPrimaryKey = $otherModelObj->primaryKey;
		$otherDisplayField = $otherModelObj->displayField;
		echo "\t\t<dt{$class}><?php __('".Inflector::humanize($otherModelClass)."') ?></dt>\n";
		echo "\t\t<dd{$class}>\n\t\t\t<?php echo \$html->link(__(\${$singularVar}['{$otherModelClass}']['{$otherDisplayField}'], true), array('controller'=> '{$otherControllerPath}', 'action'=>'view', \${$singularVar}['{$otherModelClass}']['{$otherPrimaryKey}'])); ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
	} else {
		echo "\t\t<dt{$class}><?php __('".Inflector::humanize($field['name'])."') ?></dt>\n";
		echo "\t\t<dd{$class}>\n\t\t\t<?php echo \${$singularVar}['{$modelClass}']['{$field['name']}'] ?>\n\t\t\t&nbsp;\n\t\t</dd>\n";
	}
}
?>
	</dl>
</div>
<div class="actions">
	<ul>
<?php
	echo "\t\t<li><?php echo \$html->link(sprintf(__('Edit %s', true), __('{$singularHumanName}', true)), array('action'=>'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?> </li>\n";
	echo "\t\t<li><?php echo \$html->link(sprintf(__('Delete %s', true), __('{$singularHumanName}', true)), array('action'=>'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), null, sprintf(__('Are you sure you want to delete # %s?', true), \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?> </li>\n";
	echo "\t\t<li><?php echo \$html->link(sprintf(__('List %s', true), __('{$pluralHumanName}', true)), array('action'=>'index')); ?> </li>\n";
	echo "\t\t<li><?php echo \$html->link(sprintf(__('New %s', true), __('{$singularHumanName}', true)), array('action'=>'add')); ?> </li>\n";

	foreach ($foreignKeys as $field => $value) {
		$otherModelClass = $value['1'];
		if ($otherModelClass != $modelClass) {
			$otherModelKey = Inflector::underscore($otherModelClass);
			$otherControllerName = Inflector::pluralize($otherModelClass);
			$otherControllerPath = Inflector::underscore($otherControllerName);
			$otherSingularVar = Inflector::variable($otherModelClass);
			$otherPluralHumanName = Inflector::humanize($otherControllerPath);
			$otherSingularHumanName = Inflector::humanize($otherModelKey);
			echo "\t\t<li><?php echo \$html->link(sprintf(__('List %s', true), __('{$otherPluralHumanName}', true)), array('controller'=> '{$otherControllerPath}', 'action'=>'index')); ?> </li>\n";
			echo "\t\t<li><?php echo \$html->link(sprintf(__('New %s', true), __('{$otherSingularHumanName}', true)), array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?> </li>\n";
		}
	}
	?>
	</ul>
</div>
<?php
$i = 0;
foreach ($hasOne as $assocName => $assocData):
	$otherModelKey = Inflector::underscore($assocData['className']);
	$otherControllerPath = Inflector::pluralize($otherModelKey);
	$otherControllerName = Inflector::camelize($otherControllerPath);
	$assocKey = Inflector::underscore($assocName);
	$otherPluralHumanName = Inflector::humanize(Inflector::pluralize($assocKey));
	$otherSingularHumanName = Inflector::humanize($assocKey);
	$otherModelObj =& ClassRegistry::getObject($otherModelKey);
	$otherFields = $otherModelObj->_tableInfo->value;
	$otherPrimaryKey = $otherModelObj->primaryKey;
?>
<div class="related">
	<h3><?php echo "<?php echo sprintf(__('Related %s', true), __('{$otherSingularHumanName}', true));?>";?></h3>
	<?php echo "<?php if (!empty(\${$singularVar}['{$assocName}'])):?>\n";?>
	<dl>
<?php
		$i = 0;
		foreach ($otherFields as $field) {
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}

			echo "\t\t<dt{$class}>".Inflector::humanize($field['name'])."</dt>\n";
			echo "\t\t<dd{$class}>\n\t<?php echo \${$singularVar}['{$assocName}']['{$field['name']}'] ?>\n&nbsp;</dd>\n";
		}
?>
	</dl>
	<?php echo "<?php endif; ?>\n";?>
	<div class="actions">
		<ul>
			<li><?php echo "<?php echo \$html->link(sprinft(__('Edit %s', true), __('{$otherSingularHumanName}', true)), array('controller'=> '{$otherControllerPath}', 'action'=>'edit', \${$singularVar}['{$assocName}']['{$otherPrimaryKey}']));?></li>\n";?>
		</ul>
	</div>
</div>
<?php
$i++;
endforeach;

$relations = array_merge($hasMany, $hasAndBelongsToMany);
$i = 0;
foreach ($relations as $assocName => $assocData):
	$otherModelKey = Inflector::underscore($assocData['className']);
	$otherModelObj =& ClassRegistry::getObject($otherModelKey);
	$otherControllerPath = Inflector::pluralize($otherModelKey);
	$otherControllerName = Inflector::camelize($otherControllerPath);
	$otherSingularVar = Inflector::variable($assocName);
	$assocKey = Inflector::underscore($assocName);
	$otherPluralHumanName = Inflector::humanize(Inflector::pluralize($assocKey));
	$otherSingularHumanName = Inflector::humanize($assocKey);
	$otherFields = $otherModelObj->_tableInfo->value;
	$otherPrimaryKey = $otherModelObj->primaryKey;
?>
<div class="related">
	<h3><?php echo "<?php echo sprintf(__('Related %s', true), __('{$otherPluralHumanName}', true));?>";?></h3>
	<?php echo "<?php if (!empty(\${$singularVar}['{$assocName}'])):?>\n";?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
<?php
		foreach ($otherFields as $field) {
			echo "\t\t<th><?php __('".Inflector::humanize($field['name'])."') ?></th>\n";
		}
?>
		<th class="actions"><?php echo "<?php __('Actions');?>";?></th>
	</tr>
<?php
echo "\t<?php
		\$i = 0;
		foreach (\${$singularVar}['{$assocName}'] as \${$otherSingularVar}):
			\$class = null;
			if (\$i++ % 2 == 0) {
				\$class = ' class=\"altrow\"';
			}
		?>\n";
		echo "\t\t<tr<?php echo \$class;?>>\n";

			foreach ($otherFields as $field) {
				echo "\t\t\t<td><?php echo \${$otherSingularVar}['{$field['name']}'];?></td>\n";
			}

			echo "\t\t\t<td class=\"actions\">\n";
			echo "\t\t\t\t<?php echo \$html->link(__('View', true), array('controller'=> '{$otherControllerPath}', 'action'=>'view', \${$otherSingularVar}['{$otherPrimaryKey}'])); ?>\n";
			echo "\t\t\t\t<?php echo \$html->link(__('Edit', true), array('controller'=> '{$otherControllerPath}', 'action'=>'edit', \${$otherSingularVar}['{$otherPrimaryKey}'])); ?>\n";
			echo "\t\t\t\t<?php echo \$html->link(__('Delete', true), array('controller'=> '{$otherControllerPath}', 'action'=>'delete', \${$otherSingularVar}['{$otherPrimaryKey}']), null, sprintf(__('Are you sure you want to delete # %s?', true), \${$otherSingularVar}['{$otherPrimaryKey}'])); ?>\n";
			echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";

 	echo "\t<?php endforeach; ?>\n";
?>
	</table>
	<?php echo "<?php endif; ?>\n\n";?>
	<div class="actions">
		<ul>
			<li><?php echo "<?php echo \$html->link(sprintf(__('New %s', true), __('{$otherSingularHumanName}', true)), array('controller'=> '{$otherControllerPath}', 'action'=>'add'));?> </li>\n";?>
		</ul>
	</div>
</div>
<?php endforeach;?>
