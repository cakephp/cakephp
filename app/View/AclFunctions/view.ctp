<div class="aclFunctions view">
<h2><?php  echo __('Acl Function');?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($aclFunction['AclFunction']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Acl Controller'); ?></dt>
		<dd>
			<?php echo $this->Html->link($aclFunction['AclController']['controller'], array('controller' => 'acl_controllers', 'action' => 'view', $aclFunction['AclController']['id'])); ?>
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
		<li><?php echo $this->Html->link(__('List Acl Controllers'), array('controller' => 'acl_controllers', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Controller'), array('controller' => 'acl_controllers', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php echo __('Related Acls');?></h3>
	<?php if (!empty($aclFunction['Acl'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php echo __('Id'); ?></th>
		<th><?php echo __('Acl Controller Id'); ?></th>
		<th><?php echo __('Acl Function Id'); ?></th>
		<th><?php echo __('Role Id'); ?></th>
		<th class="actions"><?php echo __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($aclFunction['Acl'] as $acl): ?>
		<tr>
			<td><?php echo $acl['id'];?></td>
			<td><?php echo $acl['acl_controller_id'];?></td>
			<td><?php echo $acl['acl_function_id'];?></td>
			<td><?php echo $acl['role_id'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View'), array('controller' => 'acls', 'action' => 'view', $acl['id'])); ?>
				<?php echo $this->Html->link(__('Edit'), array('controller' => 'acls', 'action' => 'edit', $acl['id'])); ?>
				<?php echo $this->Form->postLink(__('Delete'), array('controller' => 'acls', 'action' => 'delete', $acl['id']), null, __('Are you sure you want to delete # %s?', $acl['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
