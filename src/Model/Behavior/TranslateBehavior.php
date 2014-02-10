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

/**
 * This behavior provides a way to translate dynamic data by keeping translations
 * in a separate table linked to the original record from another one. Translated
 * fields can be configured to override those in the main table when fetched or
 * put aside into another property for the same entity.
 *
 * If you wish to override fields, you need to call the `locale` method in this
 * behavior for setting the language you want to fetch from the translations table.
 *
 * If you want to bring all or certain languages for each of the fetched records,
 * you can use the custom `translations` finders that is exposed to the table.
 */
class TranslateBehavior extends Behavior {

/**
 * Table instance
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * The locale name that will be used to override fields in the bound table
 * from the translations table
 *
 * @var string
 */
	protected $_locale;

/**
 * Default config
 *
 * These are merged with user-provided configuration when the behavior is used.
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

/**
 * Creates the associations between the bound table and every field passed to
 * this method.
 *
 * Additionally it creates a `I18n` HasMany association that will be
 * used for fetching all translations for each record in the bound table
 *
 * @param array $fields list of fields to create associations for
 * @return void
 */
	public function setupFieldAssociations($fields) {
		$alias = $this->_table->alias();
		foreach ($fields as $field) {
			$name = $this->_table->alias() . '_' . $field . '_translation';
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

/**
 * Callback method that listens to the `beforeFind` event in the bound
 * table. It modifies the passed query by eager loading the translated fields
 * and adding a formatter to copy the values into the main table records.
 *
 * @param \Cake\Event\Event $event
 * @param \Cake\ORM\Query $query
 * @return void
 */
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
		$alias = $this->_table->alias();
		foreach ($fields as $field) {
			$contain[$alias . '_' . $field . '_translation'] = $conditions;
		}

		$query->contain($contain);
		$query->formatResults(function($results) use ($locale) {
			return $this->_rowMapper($results, $locale);
		}, $query::PREPEND);
	}

	public function beforeSave(Event $event, $entity, $options) {
		$locale = $entity->get('_locale') ?: $this->locale();

		if (!$locale) {
			return;
		}

		$newOptions = ['I18n' => ['validate' => false]];
		$options['associated'] =  $newOptions + $options['associated'];
		$values = $entity->extract($this->config()['fields'], true);
		$fields = array_keys($values);
		$key = $entity->get(current((array)$this->_table->primaryKey()));

		$preexistent = TableRegistry::get('I18n')->find()
			->select(['id', 'field'])
			->where(['field IN' => $fields, 'locale' => $locale, 'foreign_key' => $key])
			->bufferResults(false)
			->indexBy('field');

		$modified = [];
		foreach ($preexistent as $field => $translation) {
			$translation->set('content', $values[$field]);
			$modified[$field] = $translation;
		}

		$new = array_diff_key($values, $modified);
		$model = $this->_table->alias();
		foreach ($new as $field => $content) {
			$new[$field] = new Entity(compact('locale', 'field', 'content', 'model'), [
				'useSetters' => false,
				'markNew' => true
			]);
		}

		$entity->set('_i18n', array_values($modified + $new));
		$entity->set('_locale', $locale, ['setter' => false]);
		$entity->dirty('_locale', false);

		foreach ($fields as $field) {
			$entity->dirty($field, false);
		}
	}

/**
 * Sets all future finds for the bound table to also fetch translated fields for
 * the passed locale. If no value is passed, it returns the currently configured
 * locale
 *
 * @param string $locale The locale to use for fetching translated records
 * @return string
 */
	public function locale($locale = null) {
		if ($locale === null) {
			return $this->_locale;
		}
		return $this->_locale = (string)$locale;
	}

/**
 * Custom finder method used to retrieve all translations for the found records.
 * Fetched translations can be filtered by locale by passing the `locales` key
 * in the options array.
 *
 * Translated values will be found for each entity under the property `_translations`,
 * containing an array indexed by locale name.
 *
 * ### Example:
 *
 * {{{
 * $article = $articles->find('translations', ['locales' => ['eng', 'deu'])->first();
 * $englishTranslatedFields = $article->get('_translations')['eng'];
 * }}}
 *
 * If the `locales` array is not passed, it will bring all translations found
 * for each record.
 *
 * @param \Cake\ORM\Query $query the original query to modify
 * @param array $options
 * @return \Cake\ORM\Query
 */
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
			}, $query::PREPEND);
	}

/**
 * Modifies the results from a table find in order to merge the translated fields
 * into each entity for a given locale.
 *
 * @param \Cake\ORM\ResultSetDecorator $results
 * @param string $locale
 * @return \Cake\Collection\Collection
 */
	protected function _rowMapper($results, $locale) {
		return $results->map(function($row) use ($locale) {
			$options = ['setter' => false, 'guard' => false];

			foreach ($this->config()['fields'] as $field) {
				$name = $field . '_translation';
				$translation = $row->get($name);

				if (!$translation) {
					continue;
				}

				$content = $translation->get('content');
				if ($content !== null) {
					$row->set($field, $content, $options);
				}

				unset($row[$name]);
			}

			$row->set('_locale', $locale, $options);
			$row->clean();
			return $row;
		});
	}

/**
 * Modifies the results from a table find in order to merge full translation records
 * into each entity under the `_translations` key
 *
 * @param \Cake\ORM\ResultSetDecorator $results
 * @return \Cake\Collection\Collection
 */
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
			$row->clean();
			return $row;
		});
	}

}
