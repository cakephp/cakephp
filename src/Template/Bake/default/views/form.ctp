<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;

$primaryKeyVar = "\${$singularVar}->{$primaryKey[0]}";
?>
<div class="<?= $pluralVar ?> form">
<?php if ($action == 'edit') : ?>
	<nav class="actions">
		<ul>
			<li><?= "<?= \$this->Html->link(__('View'), ['action' => 'view', {$primaryKeyVar}], ['title' => __('Edit {0}', h(\${$singularVar}->{$displayField}))]) ?>" ?></li>
			<li><?= "<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', {$primaryKeyVar}], ['title' => __('Delete {0}', h(\${$singularVar}->{$displayField})), 'confirm' => __('Are you sure you want to delete # {0}?', {$primaryKeyVar})]) ?>" ?></li>
		</ul>
	</nav>
<?php endif ?>
	<?= "<?= \$this->Form->create(\${$singularVar}) ?> " . PHP_EOL ?>
		<fieldset>
			<legend><?= ($action == 'add')
				? "<?= __('Add {0}', __('" . $singularHumanName . "')) ?>"
				: "<?= __('Edit {0}', h(\${$singularVar}->{$displayField})) ?>" ?></legend>
<?php foreach ($fields as $field) :?>
<?php 	if (in_array($field, $primaryKey)) : continue; endif ?>
<?php 	if (isset($keyFields[$field])) : ?>
			<?= "<?= \$this->Form->input('{$field}', ['options' => \${$keyFields[$field]}]) ?> " . PHP_EOL ?>
<?php 		continue ?>
<?php 	endif  ?>
<?php 	if (!in_array($field, ['created', 'modified'])) : ?>
			<?= "<?= \$this->Form->input('{$field}') ?> " . PHP_EOL ?>
<?php 	endif  ?>
<?php endforeach  ?>
<?php if (!empty($associations['BelongsToMany'])) : ?>
<?php 	foreach ($associations['BelongsToMany'] as $assocName => $assocData) : ?>
	 	   <?= "<?= \$this->Form->input('{$assocData['property']}._ids', ['options' => \${$assocData['variable']}]) ?> " . PHP_EOL ?>
<?php 	endforeach ?>
<?php endif ?>
		</fieldset>
		<?= "<?= \$this->Form->button(__('Save')) ?> " . PHP_EOL ?>
	<?= "<?= \$this->Form->end() ?> " . PHP_EOL ?>
</div>
<nav class="actions" id="actions_opened">
	<h3><a href="#actions_opened"><?= "<?= __('Actions') ?>" ?></a><a href="#actions_closed" id="actions_closed" title="<?= "<?=__('Close Actions Menu')?>" ?>">X</a></h3>
	<ul>
<?php if ($action == 'edit'): ?>
		<li><?= "<?= \$this->Html->link(__('List {0}', __('" . $pluralHumanName . "')), ['action' => 'index']) ?>" ?></li>
		<li class="separator"><?= "<?= \$this->Html->link(__('New {0}', __('" . $singularHumanName . "')), ['action' => 'add', {$primaryKeyVar}]) ?>" ?></li>
<?php else: ?>
		<li class="separator"><?= "<?= \$this->Html->link(__('List {0}', __('" . $pluralHumanName . "')), ['action' => 'index']) ?>" ?></li>
<?php endif?>
<?php $processedAssociations = [] ?>
<?php foreach ($associations as $type => $data) : ?>
<?php 	foreach ($data as $alias => $details) : ?>
<?php 		if ($details['controller'] != $this->name && !in_array($details['variable'], $processedAssociations)) : ?>
		<li><?= "<?= \$this->Html->link(__('List {0}', __('" . Inflector::humanize(Inflector::pluralize($details['variable'])) . "')), ['controller' => '{$details['controller']}', 'action' => 'index']) ?>" ?></li>
		<li class="separator"><?= "<?= \$this->Html->link(__('New {0}', __('" . Inflector::humanize(Inflector::singularize($details['variable'])) . "')), ['controller' => '{$details['controller']}', 'action' => 'add']) ?>" ?></li>
<?php			$processedAssociations[] = $details['variable'] ?>
<?php 		endif ?>
<?php 	endforeach ?>
<?php endforeach ?>
	</ul>
</nav>