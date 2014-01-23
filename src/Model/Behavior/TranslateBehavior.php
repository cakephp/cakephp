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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Behavior;

use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TranslateBehavior extends Behavior {

/**
 * Table instance
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

	protected $_locale;

/**
 * Default config
 *
 * These are merged with user-provided config when the behavior is used.
 *
 * @var array
 */
	protected static $_defaultConfig = [
		'implementedFinders' => [],
		'implementedMethods' => ['locale' => 'locale'],
		'fields' => []
	];

/**
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);
		$this->_table = $table;
	}

	public function setupFieldAssociations() {
		$alias = $this->_table->alias();
		foreach ($this->config()['fields'] as $field) {
			$name = $field . '_translation';
			$target = TableRegistry::get($name);
			$target->table('i18n');

			$this->_table->hasOne($name, [
				'targetTable' => $target,
				'foreignKey' => 'foreign_key',
				'joinType' => 'LEFT',
				'conditions' => [
					$name . '.model' => $alias,
					$name . '.field' => $field,
				],
				'propertyName' => $field . '_translation'
			]);
		}
	}

	public function beforeFind(Event $event, $query) {
		$fields = $this->config()['fields'];

		if (empty($fields)) {
			return;
		}

		$locale = (array)$this->locale();
		if (!$locale || count($locale) > 1) {
			return;
		}

		$this->setupFieldAssociations();
		$conditions = function($q) use ($locale) {
			return $q
				->select(['id', 'content'])
				->where([$q->repository()->alias() . '.locale IN' => $locale]);
		};

		$contain = [];
		foreach ($fields as $field) {
			$contain[$field . '_translation'] = $conditions;
		}

		$query->contain($contain);
		$locale = current($locale);
		$query->formatResults(function($results) use ($locale) {
			return $this->_rowMapper($results, $locale);
		}, $query::PREPEND);
	}

	public function locale($locale = null) {
		if ($locale === null) {
			return $this->_locale;
		}
		return $this->_locale = $locale;
	}

	protected function _rowMapper($results, $locale) {
		return $results->map(function($row) use ($locale) {
			$options = ['setter' => false, 'guard' => false];

			foreach ($this->config()['fields'] as $field) {
				$name = $field . '_translation';
				$translation = $row->get($name);

				if (!$translation) {
					continue;
				}

				$row->set([
					$field => $translation->get('content'),
					'_locale' => $locale
				], $options);
				unset($row[$name]);
			}

			$row->clean();
			return $row;
		});
	}

}
