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
<div class="<?php echo $this->action ?> <?php echo $singularVar;?>">
<?php echo $form->create($modelClass);?>
<?php echo $form->inputs(null, array('created', 'modified', 'updated'));?>
<?php
	echo $form->end(__('Submit', true));
?>
	<div class="actions">
		<ul>
	<?php if ($this->action != 'add'):?>
			<li><?php echo $html->link(__('Delete', true), array('action'=>'delete', $form->value($modelClass.'.'.$primaryKey)), null, __('Are you sure you want to delete', true).' #' . $form->value($modelClass.'.'.$primaryKey)); ?></li>
	<?php endif;?>
			<li><?php echo $html->link(__('List', true).' '.$pluralHumanName, array('action'=>'index'));?></li>
	<?php
			foreach ($foreignKeys as $field => $value) {
				$otherModelClass = $value['1'];
				if ($otherModelClass != $modelClass) {
					$otherModelKey = Inflector::underscore($otherModelClass);
					$otherControllerName = Inflector::pluralize($otherModelClass);
					$otherControllerPath = Inflector::underscore($otherControllerName);
					$otherSingularName = Inflector::variable($otherModelClass);
					$otherPluralHumanName = Inflector::humanize($otherControllerPath);
					$otherSingularHumanName = Inflector::humanize($otherModelKey);
					echo "\t\t<li>".$html->link(__('List', true).' '.$otherPluralHumanName, array('controller'=> $otherControllerPath, 'action'=>'index'))."</li>\n";
					echo "\t\t<li>".$html->link(__('New', true).' '.$otherSingularHumanName, array('controller'=> $otherControllerPath, 'action'=>'add'))."</li>\n";
				}
			}
	?>
		</ul>
	</div>
</div>