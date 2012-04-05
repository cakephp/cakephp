<div class="aclFunctions form">
<?php echo $this->Form->create('AclFunction');?>
	<fieldset>
		<legend><?php echo __('Add Acl Function'); ?></legend>
	<?php
		echo $this->Form->input('acl_controller_id');
		echo $this->Form->input('function');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Acl Functions'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Acl Controllers'), array('controller' => 'acl_controllers', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Controller'), array('controller' => 'acl_controllers', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
	</ul>
</div>
