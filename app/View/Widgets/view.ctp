<div class="widgets view">
<h2><?php echo __('Widget'); ?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($widget['Widget']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Name'); ?></dt>
		<dd>
			<?php echo h($widget['Widget']['name']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Part No'); ?></dt>
		<dd>
			<?php echo h($widget['Widget']['part_no']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Quantity'); ?></dt>
		<dd>
			<?php echo h($widget['Widget']['quantity']); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Widget'), array('action' => 'edit', $widget['Widget']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Widget'), array('action' => 'delete', $widget['Widget']['id']), array('confirm' => __('Are you sure you want to delete # %s?', $widget['Widget']['id']))); ?> </li>
		<li><?php echo $this->Html->link(__('List Widgets'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Widget'), array('action' => 'add')); ?> </li>
	</ul>
</div>
