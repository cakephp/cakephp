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
<h2><?php echo $humanPluralName;?></h2>
<?php
$modelObj =& ClassRegistry::getObject($modelKey);
?>
<p><?php
echo $paginator->counter(array(
'format' => 'Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%'
));
?></p>
<table class="scaffold" cellpadding="0" cellspacing="0">
<thead>
	<tr>
	<?php foreach ($fieldNames as $fieldName) { ?>
		<th>
			<?php
				echo $paginator->sort($fieldName['label'], $fieldName['name']);
			?>
		</th>
	<?php }?>
		<th><?php __('Actions'); ?></th>
	</tr>
</thead>
<tbody>
<?php
$i = 0;
if(is_array($data)) {
	foreach ($data as $row) {
		if($i++ % 2 == 0) {
			echo '<tr>';
		} else {
			echo '<tr class="altrow">';
		}
		foreach($fieldNames as $field => $value) {
		?>
			<td>
				<?php
					if(isset($value['foreignKey'])) {
						$otherControllerName = $value['controller'];
						$otherControllerPath = Inflector::underscore($value['controller']);
						$otherModelObject =& ClassRegistry::getObject($value['modelKey']);
						if(is_object($otherModelObject)) {
							$displayText = $row[$alias[$value['model']]][$otherModelObject->getDisplayField()];
						} else {
							$displayText = $row[$alias[$value['model']]][$field];
						}
						echo $html->link($displayText, $path . $otherControllerPath . "/view/".$row[$modelClass][$field]);
					} else {
						echo $row[$modelClass][$field];
					}
				?>
			</td>
<?php } ?>
		<td class="actions">
			<?php $id = $row[$modelClass][$modelObj->primaryKey]; ?>
			<?php echo $html->link(__('View', true), array('action' => 'view', $id)) ?>
			<?php echo $html->link(__('Edit', true), array('action' => 'edit', $id)) ?>
			<?php echo $html->link(__('Delete', true), array('action' => 'delete', $id), null, sprintf(__("Are you sure you want to delete id %s?", true), $id)) ?>
		</td>
	</tr>
<?php
	}
}?>
</tbody>
</table>
<div class="paging">
	<?php echo $paginator->prev('<< previous', array(), null, array('class'=>'disabled'));?>
 | <?php echo $paginator->numbers();?>
	<?php echo $paginator->next('next >>', array(), null, array('class'=>'disabled'));?>
</div>
<div class="actions">
	<ul>
		<?php 
			echo '<li>'.$html->link(__('New ', true).$humanSingularName, array('action' => 'add')).'</li>'; 
			
			foreach($alias as $nav) {
				if(!strpos($nav, $modelClass)) {
					$navKey = Inflector::pluralize(Inflector::underscore($nav));
					echo '<li>'.$html->link(Inflector::humanize($navKey), array('controller'=> $navKey)).'</li>';
				}
			}
			
		?>
	</ul>
</div>