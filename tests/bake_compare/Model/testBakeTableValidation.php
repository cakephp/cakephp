<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BakeArticles Model
 */
class BakeArticlesTable extends Table {

/**
 * Initialize method
 *
 * @param array $config The configuration for the Table.
 * @return void
 */
	public function initialize(array $config) {
		$this->primaryKey('id');
	}

/**
 * Default validation rules.
 *
 * @param \Cake\Validation\Validator $validator instance
 * @return \Cake\Validation\Validator
 */
	public function validationDefault(Validator $validator) {
		$validator
			->add('id', 'valid', ['rule' => 'numeric'])
			->allowEmpty('id', 'create')
			->requirePresence('name', 'create')
			->notEmpty('name')
			->add('email', 'valid', ['rule' => 'email'])
			->allowEmpty('email')
			->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

		return $validator;
	}

}
