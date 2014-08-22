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
?>
<div class="<?= $pluralVar ?> form">
<?= "<?= \$this->Form->create(\${$singularVar}) ?>\n" ?>
	<fieldset>
		<legend><?= sprintf("<?= __('%s %s'); ?>", Inflector::humanize($action), $singularHumanName) ?></legend>
<?php
		echo "\t<?php\n";
		foreach ($fields as $field) {
			if (in_array($field, $primaryKey)) {
				continue;
			}
			if (isset($keyFields[$field])) {
				echo "\t\techo \$this->Form->input('{$field}', ['options' => \${$keyFields[$field]}]);\n";
				continue;
			}
			if (!in_array($field, ['created', 'modified', 'updated'])) {
				echo "\t\techo \$this->Form->input('{$field}');\n";
			}
		}
		if (!empty($associations['BelongsToMany'])) {
			foreach ($associations['BelongsToMany'] as $assocName => $assocData) {
				echo "\t\techo \$this->Form->input('{$assocData['property']}._ids', ['options' => \${$assocData['variable']}]);\n";
			}
		}
		echo "\t?>\n";
?>
	</fieldset>
<?php
	echo "<?= \$this->Form->button(__('Submit')) ?>\n";
	echo "<?= \$this->Form->end() ?>\n";
?>
</div>
<div class="actions">
	<h3><?= "<?= __('Actions') ?>" ?></h3>
	<ul>
<?php if (strpos($action, 'add') === false): ?>
		<li><?= "<?= \$this->Form->postLink(__('Delete'), ['action' => 'delete', \${$singularVar}->{$primaryKey[0]}], ['confirm' => __('Are you sure you want to delete # %s?', \${$singularVar}->{$primaryKey[0]})]) ?>" ?></li>
<?php endif; ?>
		<li><?= "<?= \$this->Html->link(__('List " . $pluralHumanName . "'), ['action' => 'index']) ?>" ?></li>
<?php
		$done = [];
		foreach ($associations as $type => $data) {
			foreach ($data as $alias => $details) {
				if ($details['controller'] != $this->name && !in_array($details['controller'], $done)) {
					echo "\t\t<li><?= \$this->Html->link(__('List " . Inflector::humanize($details['controller']) . "'), ['controller' => '{$details['controller']}', 'action' => 'index']) ?> </li>\n";
					echo "\t\t<li><?= \$this->Html->link(__('New " . Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) . "'), ['controller' => '{$details['controller']}', 'action' => 'add']) ?> </li>\n";
					$done[] = $details['controller'];
				}
			}
		}
?>
	</ul>
</div>
