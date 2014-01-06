<div class="users form">
<!--nocache-->
	<?= $this->Form->create(false); ?>
		<fieldset>
			<legend><?= __('Add User'); ?></legend>
		<?php
			echo $this->Form->input('username');
			echo $this->Form->input('email');
			echo $this->Form->input('password');
		?>
		</fieldset>
	<?= $this->Form->end('Submit'); ?>
<!--/nocache-->
</div>
