<div class="aclRoles index">
	<h2><?php echo __('Acl Roles');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('acl_id');?></th>
			<th><?php echo $this->Paginator->sort('acl_function_id');?></th>
			<th><?php echo $this->Paginator->sort('role_id');?></th>
			<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
	foreach ($aclRoles as $aclRole): ?>
	<tr>
		<td><?php echo h($aclRole['AclRole']['id']); ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($aclRole['Acl']['controller'], array('controller' => 'acls', 'action' => 'view', $aclRole['Acl']['id'])); ?>
		</td>
		<td>
			<?php echo $this->Html->link($aclRole['AclFunction']['function'], array('controller' => 'acl_functions', 'action' => 'view', $aclRole['AclFunction']['id'])); ?>
		</td>
		<td>
			<?php echo $this->Html->link($aclRole['Role']['name'], array('controller' => 'roles', 'action' => 'view', $aclRole['Role']['id'])); ?>
		</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action' => 'view', $aclRole['AclRole']['id'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $aclRole['AclRole']['id'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $aclRole['AclRole']['id']), null, __('Are you sure you want to delete # %s?', $aclRole['AclRole']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	));
	?>	</p>

	<div class="paging">
	<?php
		echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
		echo $this->Paginator->numbers(array('separator' => ''));
		echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
	?>
	</div>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
