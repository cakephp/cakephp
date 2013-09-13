<?php
namespace TestApp\Model;

use Cake\TestSuite\Fixture\TestModel;

/**
 * PaginatorCustomPost class
 *
 * @package Cake.Test.Case.Controller.Component
 */
class PaginatorCustomPost extends TestModel {

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'posts';

/**
 * belongsTo property
 *
 * @var string
 */
	public $belongsTo = array('Author');

/**
 * findMethods property
 *
 * @var array
 */
	public $findMethods = array(
		'published' => true,
		'totals' => true,
		'totalsOperation' => true
	);

/**
 * _findPublished custom find
 *
 * @return array
 */
	protected function _findPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'Y';
			return $query;
		}
		return $results;
	}

/**
 * _findTotals custom find
 *
 * @return array
 */
	protected function _findTotals($state, $query, $results = array()) {
		if ($state == 'before') {
			$query['fields'] = array('author_id');
			$this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
			$query['fields'][] = 'total_posts';
			$query['group'] = array('author_id');
			$query['order'] = array('author_id' => 'ASC');
			return $query;
		}
		$this->virtualFields = array();
		return $results;
	}

/**
 * _findTotalsOperation custom find
 *
 * @return array
 */
	protected function _findTotalsOperation($state, $query, $results = array()) {
		if ($state == 'before') {
			if (!empty($query['operation']) && $query['operation'] === 'count') {
				unset($query['limit']);
				$query['recursive'] = -1;
				$query['fields'] = array('COUNT(DISTINCT author_id) AS count');
				return $query;
			}
			$query['recursive'] = 0;
			$query['callbacks'] = 'before';
			$query['fields'] = array('author_id', 'Author.user');
			$this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
			$query['fields'][] = 'total_posts';
			$query['group'] = array('author_id', 'Author.user');
			$query['order'] = array('author_id' => 'ASC');
			return $query;
		}
		$this->virtualFields = array();
		return $results;
	}

}
