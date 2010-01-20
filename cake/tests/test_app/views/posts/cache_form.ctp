<div class="users form">
<cake:nocache>
	<?php echo $form->create('User');?>
		<fieldset>
	 		<legend><?php __('Add User');?></legend>
		<?php
			echo $form->input('username');
			echo $form->input('email');
			echo $form->input('password');
		?>
		</fieldset>
	<?php echo $form->end('Submit');?>
</cake:nocache>
</div>