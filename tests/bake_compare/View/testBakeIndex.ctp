<div class="actions columns large-2 medium-3">
	<h3><?= __('Actions') ?></h3>
	<ul class="side-nav">
		<li><?= $this->Html->link(__('New View Task Comment'), ['action' => 'add']) ?></li>
		<li><?= $this->Html->link(__('List Articles'), ['controller' => 'Articles', 'action' => 'index']) ?> </li>
		<li><?= $this->Html->link(__('New Article'), ['controller' => 'Articles', 'action' => 'add']) ?> </li>
	</ul>
</div>
<div class="viewTaskComments index large-10 medium-9 columns">
	<table cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th><?= $this->Paginator->sort('id') ?></th>
			<th><?= $this->Paginator->sort('article_id') ?></th>
			<th><?= $this->Paginator->sort('user_id') ?></th>
			<th><?= $this->Paginator->sort('published') ?></th>
			<th><?= $this->Paginator->sort('created') ?></th>
			<th><?= $this->Paginator->sort('updated') ?></th>
			<th class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($viewTaskComments as $viewTaskComment): ?>
		<tr>
			<td><?= $this->Number->format($viewTaskComment->id) ?></td>
			<td>
				<?= $viewTaskComment->has('article') ? $this->Html->link($viewTaskComment->article->title, ['controller' => 'Articles', 'action' => 'view', $viewTaskComment->article->id]) : '' ?>
			</td>
			<td><?= $this->Number->format($viewTaskComment->user_id) ?></td>
			<td><?= h($viewTaskComment->published) ?></td>
			<td><?= h($viewTaskComment->created) ?></td>
			<td><?= h($viewTaskComment->updated) ?></td>
			<td class="actions">
				<?= $this->Html->link(__('View'), ['action' => 'view', $viewTaskComment->id]) ?>
				<?= $this->Html->link(__('Edit'), ['action' => 'edit', $viewTaskComment->id]) ?>
				<?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $viewTaskComment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $viewTaskComment->id)]) ?>
			</td>
		</tr>

	<?php endforeach; ?>
	</tbody>
	</table>
	<div class="paginator">
		<ul class="pagination">
			<?= $this->Paginator->prev('< ' . __('previous')); ?>
			<?= $this->Paginator->numbers(); ?>
			<?=	$this->Paginator->next(__('next') . ' >'); ?>
		</ul>
		<p><?= $this->Paginator->counter(); ?></p>
	</div>
</div>
