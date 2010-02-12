<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.scaffolds
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
?>
<div class="<?php echo $pluralVar;?> form">
<?php
	echo $form->create();
	echo $form->inputs($scaffoldFields, array('created', 'modified', 'updated'));
	echo $form->end(__('Submit', true));
?>
</div>
<div class="actions">
	<ul>
<?php if ($this->action != 'add'):?>
		<li><?php echo $html->link(__('Delete', true), array('action' => 'delete', $form->value($modelClass.'.'.$primaryKey)), null, __('Are you sure you want to delete', true).' #' . $form->value($modelClass.'.'.$primaryKey)); ?></li>
<?php endif;?>
		<li><?php echo $html->link(__('List', true).' '.$pluralHumanName, array('action' => 'index'));?></li>
<?php
		$done = array();
		foreach ($associations as $_type => $_data) {
			foreach ($_data as $_alias => $_details) {
				if ($_details['controller'] != $this->name && !in_array($_details['controller'], $done)) {
					echo "\t\t<li>" . $html->link(sprintf(__('List %s', true), Inflector::humanize($_details['controller'])), array('controller' => $_details['controller'], 'action' =>'index')) . "</li>\n";
					echo "\t\t<li>" . $html->link(sprintf(__('New %s', true), Inflector::humanize(Inflector::underscore($_alias))), array('controller' => $_details['controller'], 'action' =>'add')) . "</li>\n";
					$done[] = $_details['controller'];
				}
			}
		}
?>
	</ul>
</div>