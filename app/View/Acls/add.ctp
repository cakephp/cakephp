<div class="acls form">
<?php

$this->Js->get('#AclAclControllerId')->event('change',
        $this->Js->request(array(
                'controller'=>'acl_functions',
                'action'=>'ajax_list'
                ), array(
                'update'=>'#AclAclFunctionId',
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

<?php echo $this->Form->create('Acl');?>
	<fieldset>
		<legend><?php echo __('Add Acl'); ?></legend>
	<?php
		echo $this->Form->input('acl_controller_id');
		echo $this->Form->input('acl_function_id');
		echo $this->Form->input('role_id');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit'));?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Acls'), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Acl Controllers'), array('controller' => 'acl_controllers', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Controller'), array('controller' => 'acl_controllers', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Acl Functions'), array('controller' => 'acl_functions', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Acl Function'), array('controller' => 'acl_functions', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Roles'), array('controller' => 'roles', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Role'), array('controller' => 'roles', 'action' => 'add')); ?> </li>
	</ul>
</div>
