<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Html->link(__('Edit View Task Comment'), ['action' => 'edit', $viewTaskComment->id]) ?> </li>
		<li><?= $this->Form->postLink(__('Delete View Task Comment'), ['action' => 'delete', $viewTaskComment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $viewTaskComment->id)]) ?> </li>
		<li><?= $this->Html->link(__('List View Task Comments'), ['action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New View Task Comment'), ['action' => 'add']) ?> </li>
		<li><?= $this->Html->link(__('List Articles'), ['controller' => 'Articles', 'action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New Article'), ['controller' => 'Articles', 'action' => 'add']) ?> </li>
	</ul>
</div>
<div class="viewTaskComments view large-10 medium-9 columns">
	<h2><?= h($viewTaskComment->id) ?></h2>
	<div class="row">
		<div class="large-5 columns strings">
			<h6 class="subheader"><?= __('Article') ?></h6>
			<p><?= $viewTaskComment->has('article') ? $this->Html->link($viewTaskComment->article->title, ['controller' => 'Articles', 'action' => 'view', $viewTaskComment->article->id]) : '' ?></p>
			<h6 class="subheader"><?= __('Published') ?></h6>
			<p><?= h($viewTaskComment->published) ?></p>
		</div>
		<div class="large-2 columns numbers end">
			<h6 class="subheader"><?= __('Id') ?></h6>
			<p><?= $this->Number->format($viewTaskComment->id) ?></p>
			<h6 class="subheader"><?= __('User Id') ?></h6>
			<p><?= $this->Number->format($viewTaskComment->user_id) ?></p>
		</div>
		<div class="large-2 columns dates end">
			<h6 class="subheader"><?= __('Created') ?></h6>
			<p><?= h($viewTaskComment->created) ?></p>
			<h6 class="subheader"><?= __('Updated') ?></h6>
			<p><?= h($viewTaskComment->updated) ?></p>
		</div>
	</div>
	<div class="row texts">
		<div class="columns large-9">
			<h6 class="subheader"><?= __('Comment') ?></h6>
			<?= $this->Text->autoParagraph(h($viewTaskComment->comment)); ?>

		</div>
	</div>
</div>
