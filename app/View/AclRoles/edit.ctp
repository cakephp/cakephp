<div class="aclRoles form">
	
<?php 
	$functionslist = '';
	
$this->Js->get('#AclRoleAclId')->event('change', 
	$this->Js->request(array(
		'controller'=>'acl_functions',
		'action'=>'ajax_list'
		), array(
		'update'=>'#AclRoleAclFunctionId',
		'async' => true,
		'method' => 'post',
		'dataExpression'=>true,
		'data'=> $this->Js->serializeForm(array(
			'isForm' => true,
			'inline' => true
			))
		))
	);
?>

<?php echo $this->Form->create('AclRole');?>
	<fieldset>
		<legend><?php echo __('Edit Acl Role'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('acl_id');
		echo $this->Form->input('acl_function_id');
		echo $this->Form->input('role_id');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('AclRole.id')), null, __('Are you sure you want to delete # %s?', $this->Form->value('AclRole.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Acl Roles'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Acls'), array('controller' => 'acls', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl'), array('controller' => 'acls', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
