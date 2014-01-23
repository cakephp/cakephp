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
		'implementedMethods' => ['locale' => 'locale']
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

		$table->hasMany('I18n', [
			'strategy' => 'subquery',
			'foreignKey' => 'foreign_key',
			'conditions' => ['I18n.model' => $table->alias()],
			'propertyName' => '_i18n'
		]);
	}

	public function beforeFind(Event $event, $query) {
		$locale = (array)$this->locale();
		$query->contain(['I18n' => function($q) use ($locale) {
			if ($locale) {
				$q->where(['I18n.locale IN' => $locale]);
			}

			return $q;
		}]);

		if (count($locale) === 1) {
			$locale = current($locale);
			$query->formatResults(function($results) use ($locale) {
				return $this->_rowMapper($results, $locale);
			});
		}
	}

	public function locale($locale = null) {
		if ($locale === null) {
			return $this->_locale;
		}
		return $this->_locale = $locale;
	}

	protected function _rowMapper($results, $locale) {
		return $results->map(function($row) use ($locale) {
			$translations = (array)$row->get('_i18n');
			$options = ['setter' => false, 'guard' => false];

			foreach ($translations as $field) {
				$row->set([
					$field->get('field') => $field->get('content'),
					'_locale' => $locale
				], $options);
			}

			$row->clean();
			unset($row['_i18n']);
			return $row;
		});
	}

}
