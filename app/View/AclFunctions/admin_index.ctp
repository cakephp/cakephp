<div class="aclFunctions index">
	<h2><?php echo __('Acl Functions');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('acl_id');?></th>
			<th><?php echo $this->Paginator->sort('function');?></th>
			<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
	foreach ($aclFunctions as $aclFunction): ?>
	<tr>
		<td><?php echo h($aclFunction['AclFunction']['id']); ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($aclFunction['Acl']['controller'], array('controller' => 'acls', 'action' => 'view', $aclFunction['Acl']['id'])); ?>
		</td>
		<td><?php echo h($aclFunction['AclFunction']['function']); ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action' => 'view', $aclFunction['AclFunction']['id'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $aclFunction['AclFunction']['id'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $aclFunction['AclFunction']['id']), null, __('Are you sure you want to delete # %s?', $aclFunction['AclFunction']['id'])); ?>
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
		<li><?php echo $this->Html->link(__('New Acl Function'), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('controller' => 'acl_roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Role'), array('controller' => 'acl_roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
