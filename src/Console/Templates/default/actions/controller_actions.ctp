<?php
/**
 * Bake Template for Controller action generation.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>

/**
 * <?= $admin ?>index method
 *
 * @return void
 */
	public function <?= $admin ?>index() {
		$this-><?= $currentModelName ?>->recursive = 0;
		$this->set('<?= $pluralName ?>', $this->paginate());
	}

/**
 * <?= $admin ?>view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function <?= $admin ?>view($id = null) {
		if (!$this-><?= $currentModelName; ?>->exists($id)) {
			throw new NotFoundException(__('Invalid <?= strtolower($singularHumanName); ?>'));
		}
		$options = ['conditions' => ['<?= $currentModelName; ?>.' . $this-><?= $currentModelName; ?>->primaryKey => $id]];
		$this->set('<?= $singularName; ?>', $this-><?= $currentModelName; ?>->find('first', $options));
	}

<?php $compact = []; ?>
/**
 * <?= $admin ?>add method
 *
 * @return void
 */
	public function <?= $admin ?>add() {
		if ($this->request->is('post')) {
			$this-><?= $currentModelName; ?>->create();
			if ($this-><?= $currentModelName; ?>->save($this->request->data)) {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be saved. Please, try again.'));
			}
		}
<?php
	foreach (['belongsTo', 'hasAndBelongsToMany'] as $assoc):
		foreach ($modelObj->{$assoc} as $associationName => $relation):
			if (!empty($associationName)):
				$otherModelName = $this->_modelName($associationName);
				$otherPluralName = $this->_pluralName($associationName);
				echo "\t\t\${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
				$compact[] = "'{$otherPluralName}'";
			endif;
		endforeach;
	endforeach;
	if (!empty($compact)):
		echo "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
	endif;
?>
	}

<?php $compact = []; ?>
/**
 * <?= $admin ?>edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function <?= $admin; ?>edit($id = null) {
		if (!$this-><?= $currentModelName; ?>->exists($id)) {
			throw new NotFoundException(__('Invalid <?= strtolower($singularHumanName); ?>'));
		}
		if ($this->request->is(['post', 'put'])) {
			if ($this-><?= $currentModelName; ?>->save($this->request->data)) {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been saved.'));
				return $this->redirect(['action' => 'index']);
			} else {
				$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be saved. Please, try again.'));
			}
		} else {
			$options = ['conditions' => ['<?= $currentModelName; ?>.' . $this-><?= $currentModelName; ?>->primaryKey => $id]];
			$this->request->data = $this-><?= $currentModelName; ?>->find('first', $options);
		}
<?php
		foreach (['belongsTo', 'hasAndBelongsToMany'] as $assoc):
			foreach ($modelObj->{$assoc} as $associationName => $relation):
				if (!empty($associationName)):
					$otherModelName = $this->_modelName($associationName);
					$otherPluralName = $this->_pluralName($associationName);
					echo "\t\t\${$otherPluralName} = \$this->{$currentModelName}->{$otherModelName}->find('list');\n";
					$compact[] = "'{$otherPluralName}'";
				endif;
			endforeach;
		endforeach;
		if (!empty($compact)):
			echo "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
		endif;
	?>
	}

/**
 * <?= $admin ?>delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function <?= $admin; ?>delete($id = null) {
		$this-><?= $currentModelName; ?>->id = $id;
		if (!$this-><?= $currentModelName; ?>->exists()) {
			throw new NotFoundException(__('Invalid <?= strtolower($singularHumanName); ?>'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this-><?= $currentModelName; ?>->delete()) {
			$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> has been deleted.'));
		} else {
			$this->Session->setFlash(__('The <?= strtolower($singularHumanName); ?> could not be deleted. Please, try again.'));
		}
		return $this->redirect(['action' => 'index']);
	}
