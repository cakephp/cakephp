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
		'implementedFinders' => ['translations' => 'findTranslations'],
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
		$fields = $this->config()['fields'];
		$this->setupFieldAssociations($fields);
	}

	public function setupFieldAssociations($fields) {
		$alias = $this->_table->alias();
		foreach ($fields as $field) {
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

		$this->_table->hasMany('I18n', [
			'foreignKey' => 'foreign_key',
			'strategy' => 'subquery',
			'conditions' => ['I18n.model' => $alias],
			'propertyName' => '_i18n'
		]);
	}

	public function beforeFind(Event $event, $query) {
		$locale = $this->locale();

		if (empty($locale)) {
			return;
		}

		$conditions = function($q) use ($locale) {
			return $q
				->select(['id', 'content'])
				->where([$q->repository()->alias() . '.locale' => $locale]);
		};

		$contain = [];
		$fields = $this->config()['fields'];
		foreach ($fields as $field) {
			$contain[$field . '_translation'] = $conditions;
		}

		$query->contain($contain);
		$query->formatResults(function($results) use ($locale) {
			return $this->_rowMapper($results, $locale);
		}, $query::PREPEND);
	}

	public function locale($locale = null) {
		if ($locale === null) {
			return $this->_locale;
		}
		return $this->_locale = (string)$locale;
	}

	public function findTranslations($query, $options) {
		$locales = isset($options['locales']) ? $options['locales'] : [];
		return $query
			->contain(['I18n' => function($q) use ($locales) {
				if ($locales) {
					$q->where(['I18n.locale IN' => $locales]);
				}
				return $q;
			}])
			->formatResults(function($results) {
				return $this->_groupTranslations($results);
			});
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

	protected function _groupTranslations($results) {
		return $results->map(function($row) {
			$translations = (array)$row->get('_i18n');
			$grouped = new Collection($translations);

			$result = [];
			foreach ($grouped->combine('field', 'content', 'locale') as $locale => $keys) {
				$translation = new Entity($keys + ['locale' => $locale], [
					'markNew' => false,
					'useSetters' => false,
					'markClean' => true
				]);
				$result[$locale] = $translation;
			}

			$options = ['setter' => false, 'guard' => false];
			$row->set('_translations', $result, $options);
			unset($row['_i18n']);
			return $row;
		});
	}

}
