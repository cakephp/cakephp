<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Html->link(__('Edit Test View Model'), ['action' => 'edit', $testViewModel->id]) ?> </li>
		<li><?= $this->Form->postLink(__('Delete Test View Model'), ['action' => 'delete', $testViewModel->id], ['confirm' => __('Are you sure you want to delete # {0}?', $testViewModel->id)]) ?> </li>
		<li><?= $this->Html->link(__('List Test View Models'), ['action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New Test View Model'), ['action' => 'add']) ?> </li>
	</ul>
</div>
<div class="testViewModels view large-10 medium-9 columns">
	<h2><?= h($testViewModel->name) ?></h2>
	<div class="row">
		<div class="large-5 columns strings">
			<h6 class="subheader"><?= __('Name') ?></h6>
			<p><?= h($testViewModel->name) ?></p>
			<h6 class="subheader"><?= __('Body') ?></h6>
			<p><?= h($testViewModel->body) ?></p>
		</div>
		<div class="large-2 columns numbers end">
			<h6 class="subheader"><?= __('Id') ?></h6>
			<p><?= $this->Number->format($testViewModel->id) ?></p>
		</div>
	</div>
</div>
