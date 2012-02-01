<div class="aclRoles view">
<h2><?php  echo __('Acl Role');?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($aclRole['AclRole']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl'); ?></dt>
		<dd>
			<?php echo $this->Html->link($aclRole['Acl']['controller'], array('controller' => 'acls', 'action' => 'view', $aclRole['Acl']['id'])); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl Function'); ?></dt>
		<dd>
			<?php echo $this->Html->link($aclRole['AclFunction']['function'], array('controller' => 'acl_functions', 'action' => 'view', $aclRole['AclFunction']['id'])); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Role'); ?></dt>
		<dd>
			<?php echo $this->Html->link($aclRole['Role']['name'], array('controller' => 'roles', 'action' => 'view', $aclRole['Role']['id'])); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Acl Role'), array('action' => 'edit', $aclRole['AclRole']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Acl Role'), array('action' => 'delete', $aclRole['AclRole']['id']), null, __('Are you sure you want to delete # %s?', $aclRole['AclRole']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
