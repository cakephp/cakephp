<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.templates.views
 * @since         CakePHP(tm) v 1.2.0.5234
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<div class="<?php echo $pluralVar;?> index">
<h2><?php echo "<?php __('{$pluralHumanName}');?>";?></h2>
<p>
<?php echo "<?php
echo \$paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?>";?>
</p>
<table cellpadding="0" cellspacing="0">
<tr>
<?php  foreach ($fields as $field):?>
	<th><?php echo "<?php echo \$paginator->sort('{$field}');?>";?></th>
<?php endforeach;?>
	<th class="actions"><?php echo "<?php __('Actions');?>";?></th>
</tr>
<?php
echo "<?php
\$i = 0;
foreach (\${$pluralVar} as \${$singularVar}):
	\$class = null;
	if (\$i++ % 2 == 0) {
		\$class = ' class=\"altrow\"';
	}
?>\n";
	echo "\t<tr<?php echo \$class;?>>\n";
		foreach ($fields as $field) {
			$isKey = false;
			if (!empty($associations['belongsTo'])) {
				foreach ($associations['belongsTo'] as $alias => $details) {
					if ($field === $details['foreignKey']) {
						$isKey = true;
						echo "\t\t<td>\n\t\t\t<?php echo \$html->link(\${$singularVar}['{$alias}']['{$details['displayField']}'], array('controller' => '{$details['controller']}', 'action' => 'view', \${$singularVar}['{$alias}']['{$details['primaryKey']}'])); ?>\n\t\t</td>\n";
						break;
					}
				}
			}
			if ($isKey !== true) {
				echo "\t\t<td>\n\t\t\t<?php echo \${$singularVar}['{$modelClass}']['{$field}']; ?>\n\t\t</td>\n";
			}
		}

		echo "\t\t<td class=\"actions\">\n";
		echo "\t\t\t<?php echo \$html->link(__('View', true), array('action' => 'view', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
	 	echo "\t\t\t<?php echo \$html->link(__('Edit', true), array('action' => 'edit', \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
	 	echo "\t\t\t<?php echo \$html->link(__('Delete', true), array('action' => 'delete', \${$singularVar}['{$modelClass}']['{$primaryKey}']), null, sprintf(__('Are you sure you want to delete # %s?', true), \${$singularVar}['{$modelClass}']['{$primaryKey}'])); ?>\n";
		echo "\t\t</td>\n";
	echo "\t</tr>\n";

echo "<?php endforeach; ?>\n";
?>
</table>
</div>
<div class="paging">
<?php echo "\t<?php echo \$paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));?>\n";?>
 | <?php echo "\t<?php echo \$paginator->numbers();?>\n"?>
<?php echo "\t<?php echo \$paginator->next(__('next', true).' >>', array(), null, array('class' => 'disabled'));?>\n";?>
</div>
<div class="actions">
	<ul>
		<li><?php echo "<?php echo \$html->link(__('New {$singularHumanName}', true), array('action' => 'add')); ?>";?></li>
<?php
	$done = array();
	foreach ($associations as $type => $data) {
		foreach ($data as $alias => $details) {
			if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
				echo "\t\t<li><?php echo \$html->link(__('List " . Inflector::humanize($details['controller']) . "', true), array('controller' => '{$details['controller']}', 'action' => 'index')); ?> </li>\n";
				echo "\t\t<li><?php echo \$html->link(__('New " . Inflector::humanize(Inflector::underscore($alias)) . "', true), array('controller' => '{$details['controller']}', 'action' => 'add')); ?> </li>\n";
				$done[] = $details['controller'];
			}
		}
	}
?>
	</ul>
</div>
