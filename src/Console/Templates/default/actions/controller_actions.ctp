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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
$extractor = function ($val) {
	return $val->target()->alias();
};
$stringifyList = function ($list) {
	$wrapped = array_map(function ($v) {
		return "'$v'";
	}, $list);
	return implode(', ', $wrapped);
};

$belongsTo = array_map($extractor, $modelObj->associations()->type('BelongsTo'));
$belongsToMany = array_map($extractor, $modelObj->associations()->type('BelongsToMany'));

$editAssociations = array_merge($belongsTo, $belongsToMany);

$allAssociations = array_merge(
	$editAssociations,
	array_map($extractor, $modelObj->associations()->type('HasOne')),
	array_map($extractor, $modelObj->associations()->type('HasMany'))
);
?>

/**
 * Index method
 *
 * @return void
 */
	public function index() {
<?php if ($belongsTo): ?>
		$this->paginate = [
			'contain' => [<?= $stringifyList($belongsTo) ?>]
		];
<?php endif; ?>
		$this->set('<?= $pluralName ?>', $this->paginate($this-><?= $currentModelName ?>));
	}

/**
 * View method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$<?= $singularName?> = $this-><?= $currentModelName ?>->get($id, [
			'contain' => [<?= $stringifyList($allAssociations) ?>]
		]);
		$this->set('<?= $singularName; ?>', $<?= $singularName; ?>);
	}

<?php $compact = ["'" . $singularName . "'"]; ?>
/**
 * Add method
 *
 * @return void
 */
	public function add() {
		$<?= $singularName ?> = $this-><?= $currentModelName ?>->newEntity($this->request->data);
		if ($this->request->is('post')) {
			if ($this-><?= $currentModelName; ?>->save($<?= $singularName ?>)) {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be saved. Please, try again.'));
			}
		}
<?php
		foreach ($editAssociations as $assoc):
			$association = $modelObj->association($assoc);
			$otherName = $association->target()->alias();
			$otherPlural = $this->_pluralName($otherName);
			echo "\t\t\${$otherPlural} = \$this->{$currentModelName}->{$otherName}->find('list');\n";
			$compact[] = "'{$otherPlural}'";
		endforeach;
		echo "\t\t\$this->set(compact(" . join(', ', $compact) . "));\n";
?>
	}

<?php $compact = ["'" . $singularName . "'"]; ?>
/**
 * Edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$<?= $singularName ?> = $this-><?= $currentModelName ?>->get($id, [
			'contain' => [<?= $stringifyList($belongsToMany) ?>]
		]);
		if ($this->request->is(['post', 'put'])) {
			$<?= $singularName ?> = $this-><?= $currentModelName ?>->patchEntity($<?= $singularName ?>, $this->request->data);
			if ($this-><?= $currentModelName; ?>->save($<?= $singularName ?>)) {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be saved. Please, try again.'));
			}
		}
<?php
		foreach ($editAssociations as $assoc):
			$association = $modelObj->association($assoc);
			$otherName = $association->target()->alias();
			$otherPlural = $this->_pluralName($otherName);
			echo "\t\t\${$otherPlural} = \$this->{$currentModelName}->{$otherName}->find('list');\n";
			$compact[] = "'{$otherPlural}'";
		endforeach;
		echo "\t\t\$this->set(compact(" . join(', ', $compact) . "));\n";
	?>
	}

/**
 * Delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$<?= $singularName ?> = $this-><?= $currentModelName ?>->get($id);
		$this->request->allowMethod('post', 'delete');
		if ($this-><?= $currentModelName; ?>->delete($<?= $singularName ?>)) {
			$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been deleted.'));
		} else {
			$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be deleted. Please, try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}
