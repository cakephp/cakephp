<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Html->link(__('Edit View Task Comment'), ['action' => 'edit', $viewTaskComment->id]) ?> </li>
		<li><?= $this->Form->postLink(__('Delete View Task Comment'), ['action' => 'delete', $viewTaskComment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $viewTaskComment->id)]) ?> </li>
		<li><?= $this->Html->link(__('List View Task Comments'), ['action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New View Task Comment'), ['action' => 'add']) ?> </li>
		<li><?= $this->Html->link(__('List Authors'), ['controller' => 'ViewTaskAuthors', 'action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New Author'), ['controller' => 'ViewTaskAuthors', 'action' => 'add']) ?> </li>
	</ul>
</div>
<div class="viewTaskComments view large-10 medium-9 columns">
	<h2><?= h($viewTaskComment->name) ?></h2>
	<div class="row">
		<div class="large-5 columns strings">
			<h6 class="subheader"><?= __('Name') ?></h6>
			<p><?= h($viewTaskComment->name) ?></p>
			<h6 class="subheader"><?= __('Body') ?></h6>
			<p><?= h($viewTaskComment->body) ?></p>
		</div>
		<div class="large-2 columns numbers end">
			<h6 class="subheader"><?= __('Id') ?></h6>
			<p><?= $this->Number->format($viewTaskComment->id) ?></p>
		</div>
	</div>
</div>
