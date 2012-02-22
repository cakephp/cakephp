<div class="aclFunctions view">
<h2><?php  echo __('Acl Function');?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($aclFunction['AclFunction']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl'); ?></dt>
		<dd>
			<?php echo $this->Html->link($aclFunction['Acl']['controller'], array('controller' => 'acls', 'action' => 'view', $aclFunction['Acl']['id'])); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Function'); ?></dt>
		<dd>
			<?php echo h($aclFunction['AclFunction']['function']); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Acl Function'), array('action' => 'edit', $aclFunction['AclFunction']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Acl Function'), array('action' => 'delete', $aclFunction['AclFunction']['id']), null, __('Are you sure you want to delete # %s?', $aclFunction['AclFunction']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('controller' => 'acl_roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php echo __('Related Acl Roles');?></h3>
	<?php if (!empty($aclFunction['AclRole'])):?>
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
		foreach ($aclFunction['AclRole'] as $aclRole): ?>
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
