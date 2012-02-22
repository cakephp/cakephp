<div class="acls view">
<h2><?php  echo __('Acl');?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($acl['Acl']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Controller'); ?></dt>
		<dd>
			<?php echo h($acl['Acl']['controller']); ?>
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
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('controller' => 'acl_roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php echo __('Related Acl Functions');?></h3>
	<?php if (!empty($acl['AclFunction'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php echo __('Id'); ?></th>
		<th><?php echo __('Acl Id'); ?></th>
		<th><?php echo __('Function'); ?></th>
		<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($acl['AclFunction'] as $aclFunction): ?>
		<tr>
			<td><?php echo $aclFunction['id'];?></td>
			<td><?php echo $aclFunction['acl_id'];?></td>
			<td><?php echo $aclFunction['function'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View'), array('controller' => 'acl_functions', 'action' => 'view', $aclFunction['id'])); ?>
				<?php echo $this->Html->link(__('Edit'), array('controller' => 'acl_functions', 'action' => 'edit', $aclFunction['id'])); ?>
				<?php echo $this->Form->postLink(__('Delete'), array('controller' => 'acl_functions', 'action' => 'delete', $aclFunction['id']), null, __('Are you sure you want to delete # %s?', $aclFunction['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
<div class="related">
	<h3><?php echo __('Related Acl Roles');?></h3>
	<?php if (!empty($acl['AclRole'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php echo __('Id'); ?></th>
		<th><?php echo __('Acl Id'); ?></th>
		<th><?php echo __('Acl Function Id'); ?></th>
		<th><?php echo __('Role Id'); ?></th>
		<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($acl['AclRole'] as $aclRole): ?>
		<tr>
			<td><?php echo $aclRole['id'];?></td>
			<td><?php echo $aclRole['acl_id'];?></td>
			<td><?php echo $aclRole['acl_function_id'];?></td>
			<td><?php echo $aclRole['role_id'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View'), array('controller' => 'acl_roles', 'action' => 'view', $aclRole['id'])); ?>
				<?php echo $this->Html->link(__('Edit'), array('controller' => 'acl_roles', 'action' => 'edit', $aclRole['id'])); ?>
				<?php echo $this->Form->postLink(__('Delete'), array('controller' => 'acl_roles', 'action' => 'delete', $aclRole['id']), null, __('Are you sure you want to delete # %s?', $aclRole['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
