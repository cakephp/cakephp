<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Article table class
 *
 */
class ArticlesTable extends Table {

	public function initialize(array $config) {
		$this->belongsTo('authors');
		$this->belongsToMany('tags');
		$this->hasMany('ArticlesTags');
	}

/**
 * Find published
 *
 * @param Cake\ORM\Query $query The query
 * @return Cake\ORM\Query
 */
	public function findPublished($query) {
		return $query->where(['published' => 'Y']);
	}

/**
 * Example public method
 *
 * @return void
 */
	public function doSomething() {
	}

/**
 * Example Secondary public method
 *
 * @return void
 */
	public function doSomethingElse() {
	}

/**
 * Custom finder, used with fixture data to ensure Paginator is sending options
 *
 * @param Cake\ORM\Query $query
 * @param array $options
 * @return Cake\ORM\Query
 */
	public function findCustomTags(Query $query, array $options = []) {
		if (isset($options['tags']) && is_array($options['tags'])) {
			return $query->matching('Tags', function($q) use ($options) {
				return $q->where(['Tags.id IN' => $options['tags']]);
			});
		}
		return $query;
	}

/**
 * Example protected method
 *
 * @return void
 */
	protected function _innerMethod() {
	}

}
