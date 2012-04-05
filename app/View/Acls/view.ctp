<div class="acls view">
<h2><?php  echo __('Acl');?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($acl['Acl']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl Controller'); ?></dt>
		<dd>
			<?php echo $this->Html->link($acl['AclController']['controller'], array('controller' => 'acl_controllers', 'action' => 'view', $acl['AclController']['id'])); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl Function'); ?></dt>
		<dd>
			<?php echo $this->Html->link($acl['AclFunction']['function'], array('controller' => 'acl_functions', 'action' => 'view', $acl['AclFunction']['id'])); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Role'); ?></dt>
		<dd>
			<?php echo $this->Html->link($acl['Role']['name'], array('controller' => 'roles', 'action' => 'view', $acl['Role']['id'])); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Acl'), array('action' => 'edit', $acl['Acl']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Acl'), array('action' => 'delete', $acl['Acl']['id']), null, __('Are you sure you want to delete # %s?', $acl['Acl']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Acls'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Controllers'), array('controller' => 'acl_controllers', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Controller'), array('controller' => 'acl_controllers', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
