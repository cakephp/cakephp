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
		$this->belongsTo('SomethingElse', [
			'alias' => 'SomethingElse',
			'foreignKey' => 'something_else_id'
		]);
		$this->belongsTo('BakeUser', [
			'alias' => 'BakeUser',
			'foreignKey' => 'bake_user_id'
		]);
		$this->hasMany('BakeComment', [
			'alias' => 'BakeComment',
			'foreignKey' => 'parent_id'
		]);
		$this->belongsToMany('BakeTag', [
			'alias' => 'BakeTag',
			'foreignKey' => 'bake_article_id',
			'joinTable' => 'bake_articles_bake_tags',
			'targetForeignKey' => 'bake_tag_id'
		]);
	}

}
