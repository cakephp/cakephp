<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Form->postLink(
				__('Delete'),
				['action' => 'delete', $viewTaskComment->id],
				['confirm' => __('Are you sure you want to delete # {0}?', $viewTaskComment->id)]
			)
		?></li>
		<li><?= $this->Html->link(__('List View Task Comments'), ['action' => 'index']) ?></li>
		<li><?= $this->Html->link(__('List Articles'), ['controller' => 'Articles', 'action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New Article'), ['controller' => 'Articles', 'action' => 'add']) ?> </li>
	</ul>
</div>
<div class="viewTaskComments form large-10 medium-9 columns">
	<?= $this->Form->create($viewTaskComment); ?>
	<fieldset>
		<legend><?= __('Edit View Task Comment') ?></legend>
		<?php
			echo $this->Form->input('article_id', ['options' => $articles]);
			echo $this->Form->input('user_id');
			echo $this->Form->input('comment');
			echo $this->Form->input('published');
		?>
	</fieldset>
	<?= $this->Form->button(__('Submit')) ?>
	<?= $this->Form->end() ?>
</div>
