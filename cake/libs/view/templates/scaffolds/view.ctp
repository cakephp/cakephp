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
 * @subpackage		cake.cake.libs.view.templates.scaffolds
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<?php
$modelObj =& ClassRegistry::getObject($modelKey);
?>
<h2><?php echo sprintf(__("View %s", true), $humanSingularName)?></h2>
<dl>
<?php
$i = 0;
foreach($fieldNames as $field => $value) {
	if($i++ % 2 == 0) {
		$class = 'class="altrow"';
	} else {
		$class = null;
	}
	echo "<dt {$class}>".$value['label']."</dt>";
	if(isset($value['foreignKey'])) {
		$otherControllerName = $value['controller'];
		$otherControllerPath = Inflector::underscore($value['controller']);
		$otherModelObj =& ClassRegistry::getObject($value['modelKey']);
		$othereDisplayField = $otherModelObj->getDisplayField();
		$displayText = $data[$alias[$value['model']]][$othereDisplayField];
		if(!empty($data[$modelClass][$field]) && (!empty($displayText))) {
			echo "<dd {$class}>".$html->link($displayText, $path . $otherControllerPath.'/view/'
							.$data[$modelClass][$field] )."</dd>";
		} else {
			echo "<dd {$class}>&nbsp;</dd>";
		}
	} else {
		if( !empty($data[$modelClass][$field])) {
			echo "<dd {$class}>".$data[$modelClass][$field]."</dd>";
		} else {
			echo "<dd {$class}>&nbsp;</dd>";
		}
	}
}?>
</dl>
<div class='actions'>
<ul>
<?php
echo '<li>' . $html->link(sprintf(__("Edit %s", true), $humanSingularName), array('action' => 'edit', $data[$modelClass][$modelObj->primaryKey])) . '</li>';
echo '<li>' . $html->link(sprintf(__("Delete %s", true), $humanSingularName), array('action' => 'delete', $data[$modelClass][$modelObj->primaryKey]), null, sprintf(__("Are you sure you want to delete id %s?", true), $data[$modelClass][$modelObj->primaryKey])) . '</li>';
echo '<li>' . $html->link(sprintf(__("List %s", true), $humanPluralName), array('action' => 'index')) . '</li>';
echo '<li>' . $html->link(sprintf(__("New %s", true), $humanSingularName), array('action' => 'add')) . '</li>';

foreach($fieldNames as $field => $value) {
	if(isset($value['foreignKey'])) {
		echo "<li>".$html->link(sprintf(__("List %s", true), Inflector::humanize($value['controller'])), array('controller' => $value['controller'], 'action' => 'index')) . '</li>';
		echo "<li>".$html->link(sprintf(__("Add %s", true), Inflector::humanize($value['controller'])), array('controller' => $value['controller'], 'action'=>'add'))."</li>";
	}
}?>
</ul>
</div>
<!--hasOne relationships -->
<?php
$j = 0;
foreach ($modelObj->hasOne as $associationNameName => $relation) {
	$otherModelKey = Inflector::underscore($relation['className']);
	$otherModelObj =& ClassRegistry::getObject($otherModelKey);
	$otherControllerPath = Inflector::pluralize($otherModelKey);
	$new = true;
	if($j++ % 2 == 0) {
		$class = 'class="altrow"';
	} else {
		$class = null;
	}
	echo "<div class=\"related\">";
	echo "<h3>".sprintf(__("Related %s", true), Inflector::humanize($associationNameName))."</h3>";
	if(!empty($data[$associationNameName])) {
		echo "<dl>";
		foreach($data[$associationNameName] as $field => $value) {
			if(isset($value)) {
				echo "<dt {$class}>".Inflector::humanize($field)."</dt>";
				if(!empty($value)) {
					echo "<dd {$class}>".$value."</dd>";
				} else {
					echo "<dd {$class}>&nbsp;</dd>";
				}
				$new = null;
			}
		}
		echo "</dl>";
	}
	echo "<div class=\"actions\">";
		echo "<ul>";
		if($new == null) {
			echo "<li>".$html->link(sprintf(__("Edit %s", true), Inflector::humanize($associationNameName)), array('controller'=> $otherControllerPath, 'action'=>'edit', $data[$associationNameName][$otherModelObj->primaryKey]))."</li>";
		} else {
			echo "<li>".$html->link(sprintf(__("New %s", true), Inflector::humanize($associationNameName)), array('controller'=> $otherControllerPath, 'action'=>'add'))."</li>";
		}
		echo "</ul>";
	echo "</div>";
echo "</div>";
}
?>

<!--hasMany and  hasAndBelongsToMany relationships -->
<?php
$relations = array_merge($modelObj->hasMany, $modelObj->hasAndBelongsToMany);
foreach($relations as $associationName => $relation) {

	$otherModelKey = Inflector::underscore($relation['className']);
	$otherModelObj = &ClassRegistry::getObject($otherModelKey);
	$otherControllerPath = Inflector::pluralize($otherModelKey);

	$otherModelName = $relation['className'];

	echo "<div class=\"related\">";
	echo "<h3>".sprintf(__("Related %s", true), Inflector::humanize($otherControllerPath))."</h3>";
	if(isset($data[$associationName][0]) && is_array($data[$associationName])) {?>
		<table cellspacing="0">
			<tr>
<?php
		$bFound = false;
		foreach($data[$associationName][0] as $column => $value) {
			if(false !== strpos($column, "_id")) {
				$column = substr($column, 0, strpos($column, "_id" ));
			}
			echo "<th>".Inflector::humanize($column)."</th>";
		}?>
				<th>Actions</th>
			</tr>
<?php
		foreach($data[$associationName] as $row) {
			echo "<tr>";
			foreach($row as $column => $value) {
				echo "<td>".$value."</td>";
			}

?>				
				<td class="actions">
					<?php echo $html->link(__('View', true), array('controller'=> $otherControllerPath, 'action'=>'view', $row[$otherModelObj->primaryKey]))?>
					<?php echo $html->link(__('Edit', true), array('controller'=> $otherControllerPath, 'action'=>'edit', $row[$otherModelObj->primaryKey]))?>
					<?php echo $html->link(__('Delete', true), array('controller'=> $otherControllerPath, 'action'=>'delete', $row[$otherModelObj->primaryKey]), null, sprintf(__("Are you sure you want to delete id %s?", true), $row[$otherModelObj->primaryKey]))?>
				</td>
<?php
			echo "</tr>";
		}
?>
		</table>
<?php
	}?>
	<div class="actions">
		<ul>
		<?php echo "<li>".$html->link(__('New ', true).Inflector::humanize($associationName), array('controller'=> $otherControllerPath, 'action'=>'add')) . '</li>'; ?>
		</ul>
	</div>
</div>
<?php }?>