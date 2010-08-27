<div class="users form">
<cake:nocache>
	<?php echo $this->Form->create('User');?>
		<fieldset>
	 		<legend><?php echo __('Add User');?></legend>
		<?php
			echo $this->Form->input('username');
			echo $this->Form->input('email');
			echo $this->Form->input('password');
		?>
		</fieldset>
	<?php echo $this->Form->end('Submit');?>
</cake:nocache>
</div>