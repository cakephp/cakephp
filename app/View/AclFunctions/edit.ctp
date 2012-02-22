<div class="aclFunctions form">
<?php echo $this->Form->create('AclFunction');?>
	<fieldset>
		<legend><?php echo __('Edit Acl Function'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('acl_id');
		echo $this->Form->input('function');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('AclFunction.id')), null, __('Are you sure you want to delete # %s?', $this->Form->value('AclFunction.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('controller' => 'acl_roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
