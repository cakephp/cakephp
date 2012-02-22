<div class="acls form">
<?php echo $this->Form->create('Acl');?>
	<fieldset>
		<legend><?php echo __('Admin Add Acl'); ?></legend>
	<?php
		echo $this->Form->input('controller');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Acls'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('controller' => 'acl_roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
