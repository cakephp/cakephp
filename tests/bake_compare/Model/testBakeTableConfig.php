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
		$this->table('articles');
		$this->displayField('title');
		$this->primaryKey('id');
		$this->addBehavior('Timestamp');
	}

}
