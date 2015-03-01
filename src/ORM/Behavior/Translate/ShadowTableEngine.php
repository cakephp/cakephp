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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\ORM\Behavior\Translate;

use ArrayObject;
use Cake\Database\Expression\FieldInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * ShadowTranslate behavior
 */
class ShadowTableEngine extends EavEngine
{
    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $config Configuration
     */
    public function __construct(Table $table, array $config = [])
    {
        $config += [
            'mainTableAlias' => $table->alias(),
            'alias' => $table->alias() . 'Translations',
            'translationTable' => $table->table() . '_translations',
            'fields' => [],
            'onlyTranslated' => false,
        ];
        parent::__construct($table, $config);
    }

    /**
     * Create a hasMany association for all records
     *
     * Don't create a hasOne association here as the join conditions are modified
     * in before find - so create/modify it there
     *
     * @param array $fields - ignored
     * @param string $table - ignored
     * @param string $model - ignored
     * @param string $strategy the strategy used in the _i18n association
     *
     * @return void
     */
    public function setupFieldAssociations($fields, $table, $model, $strategy)
    {
        $targetAlias = $this->_translationTable->alias();
        $this->_table->hasMany($targetAlias, [
            'foreignKey' => 'id',
            'strategy' => $strategy,
            'propertyName' => '_i18n',
            'dependent' => true
        ]);
    }

    /**
     * Callback method that listens to the `beforeFind` event in the bound
     * table. It modifies the passed query by eager loading the translated fields
     * and adding a formatter to copy the values into the main table records.
     *
     * @param \Cake\Event\Event $event The beforeFind event that was fired.
     * @param \Cake\ORM\Query $query Query
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function beforeFind(Event $event, Query $query, $options)
    {
        $locale = $this->locale();

        if ($locale === $this->config('defaultLocale')) {
            return;
        }

        $config = $this->config();

        if (isset($options['filterByCurrentLocale'])) {
            $joinType = $options['filterByCurrentLocale'] ? 'INNER' : 'LEFT';
        } else {
            $joinType = $config['onlyTranslated'] ? 'INNER' : 'LEFT';
        }

        $this->_table->hasOne($config['alias'], [
            'foreignKey' => ['id'],
            'joinType' => $joinType,
            'propertyName' => 'translation',
            'conditions' => [
                $config['alias'] . '.locale' => $locale,
            ],
        ]);
        $query->contain([$config['alias']]);

        $this->_addFieldsToQuery($query, $config);
        $this->_iterateClause($query, 'order', $config);
        $this->_traverseClause($query, 'where', $config);

        $query->formatResults(function ($results) use ($locale) {
            return $this->_rowMapper($results, $locale);
        }, $query::PREPEND);
    }

    /**
     * Add translation fields to query
     *
     * If the query is using autofields (directly or implicitly) add the
     * main table's fields to the query first.
     *
     * Only add translations for fields that are in the main table, always
     * add the locale field though.
     *
     * @param \Cake\ORM\Query $query the query to check
     * @param array $config the config to use for adding fields
     * @return void
     */
    protected function _addFieldsToQuery(Query $query, array $config)
    {
        $select = $query->clause('select');
        $addAll = false;

        if (!count($select) || $query->autoFields() === true) {
            $addAll = true;
            $query->select($query->repository()->schema()->columns());
            $select = $query->clause('select');
        }

        $alias = $config['mainTableAlias'];
        foreach ($this->_translationFields() as $field) {
            if ($addAll ||
                in_array($field, $select, true) ||
                in_array("$alias.$field", $select, true)
            ) {
                $query->select($query->aliasField($field, $config['alias']));
            }
        }
        $query->select($query->aliasField('locale', $config['alias']));
    }

    /**
     * Iterate over a clause to alias fields
     *
     * The objective here is to transparently prevent ambiguous field errors by
     * prefixing fields with the appropriate table alias. This method currently
     * expects to receive an order clause only.
     *
     * @param \Cake\ORM\Query $query the query to check
     * @param string $name The clause name
     * @param array $config the config to use for adding fields
     * @return void
     */
    protected function _iterateClause(Query $query, $name = '', $config = [])
    {
        $clause = $query->clause($name);
        if (!$clause || !$clause->count()) {
            return;
        }

        $alias = $config['alias'];
        $fields = $this->_translationFields();
        $mainTableAlias = $config['mainTableAlias'];
        $mainTableFields = $this->_mainFields();

        $clause->iterateParts(function ($c, $field) use ($fields, $alias, $mainTableAlias, $mainTableFields) {
            if (!is_string($field) || strpos($field, '.')) {
                return;
            }

            if (in_array($field, $fields)) {
                $field = "$alias.$field";

                return;
            }

            if (in_array($field, $mainTableFields)) {
                $field = "$mainTableAlias.$field";
            }
        });
    }

    /**
     * Traverse over a clause to alias fields
     *
     * The objective here is to transparently prevent ambiguous field errors by
     * prefixing fields with the appropriate table alias. This method currently
     * expects to receive a where clause only.
     *
     * @param \Cake\ORM\Query $query the query to check
     * @param string $name The clause name
     * @param array $config the config to use for adding fields
     * @return void
     */
    protected function _traverseClause(Query $query, $name = '', $config = [])
    {
        $clause = $query->clause($name);
        if (!$clause || !$clause->count()) {
            return;
        }

        $alias = $config['alias'];
        $fields = $this->_translationFields();
        $mainTableAlias = $config['mainTableAlias'];
        $mainTableFields = $this->_mainFields();

        $clause->traverse(function ($expression) use ($fields, $alias, $mainTableAlias, $mainTableFields) {
            if (!($expression instanceof FieldInterface)) {
                return;
            }
            $field = $expression->getField();
            if (!$field || strpos($field, '.')) {
                return;
            }

            if (in_array($field, $fields)) {
                $expression->setField("$alias.$field");

                return;
            }

            if (in_array($field, $mainTableFields)) {
                $expression->setField("$mainTableAlias.$field");
            }
        });
    }

    /**
     * Modifies the entity before it is saved so that translated fields are persisted
     * in the database too.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\ORM\Entity $entity The entity that is going to be saved
     * @param \ArrayObject $options the options passed to the save method
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $locale = $entity->get('_locale') ?: $this->locale();
        $table = $this->_config['translationTable'];
        $newOptions = [$table => ['validate' => false]];
        $options['associated'] = $newOptions + $options['associated'];

        $this->_bundleTranslatedFields($entity);
        $bundled = $entity->get('_i18n') ?: [];

        if ($locale === $this->config('defaultLocale')) {
            return;
        }

        $values = $entity->extract($this->_config['fields'], true);
        $fields = array_keys($values);
        $primaryKey = (array)$this->_table->primaryKey();
        $key = $entity->get(current($primaryKey));

        $translation = $this->_translationTable()->find()
            ->select(array_merge(['id', 'locale'], $fields))
            ->where(['locale' => $locale, 'id' => $key])
            ->bufferResults(false)
            ->first();

        if ($translation) {
            foreach ($fields as $field) {
                $translation->set($field, $values[$field]);
            }
        } else {
            $translation = new Entity(['id' => $key, 'locale' => $locale] + $values, [
                'useSetters' => false,
                'markNew' => true
            ]);
        }

        $entity->set('_i18n', array_merge($bundled, [$translation]));
        $entity->set('_locale', $locale, ['setter' => false]);
        $entity->dirty('_locale', false);

        foreach ($fields as $field) {
            $entity->dirty($field, false);
        }
    }

    /**
     * Modifies the results from a table find in order to merge the translated fields
     * into each entity for a given locale.
     *
     * @param \Cake\Datasource\ResultSetInterface $results Results to map.
     * @param string $locale Locale string
     * @return \Cake\Collection\Collection
     */
    protected function _rowMapper($results, $locale)
    {
        return $results->map(function ($row) {
            if ($row === null) {
                return $row;
            }

            $hydrated = !is_array($row);

            if (empty($row['translation'])) {
                $row['_locale'] = $this->locale();
                unset($row['translation']);

                if ($hydrated) {
                    $row->clean();
                }

                return $row;
            }

            $translation = $row['translation'];

            $keys = $hydrated ? $translation->visibleProperties() : array_keys($translation);

            foreach ($keys as $field) {
                if ($field === 'locale') {
                    $row['_locale'] = $translation[$field];
                    continue;
                }

                if ($translation[$field] !== null) {
                    $row[$field] = $translation[$field];
                }
            }

            unset($row['translation']);

            if ($hydrated) {
                $row->clean();
            }

            return $row;
        });
    }

    /**
     * Modifies the results from a table find in order to merge full translation records
     * into each entity under the `_translations` key
     *
     * @param \Cake\Datasource\ResultSetInterface $results Results to modify.
     * @return \Cake\Collection\Collection
     */
    public function groupTranslations($results)
    {
        return $results->map(function ($row) {
            $translations = (array)$row->get('_i18n');

            $result = [];
            foreach ($translations as $translation) {
                unset($translation['id']);
                $result[$translation['locale']] = $translation;
            }

            $options = ['setter' => false, 'guard' => false];
            $row->set('_translations', $result, $options);
            unset($row['_i18n']);
            $row->clean();

            return $row;
        });
    }

    public function afterSave(Event $event, Entity $entity) {

    }

    /**
     * Helper method used to generated multiple translated field entities
     * out of the data found in the `_translations` property in the passed
     * entity. The result will be put into its `_i18n` property
     *
     * @param \Cake\ORM\Entity $entity Entity
     * @return void
     */
    protected function _bundleTranslatedFields($entity)
    {
        $translations = (array)$entity->get('_translations');

        if (empty($translations) && !$entity->dirty('_translations')) {
            return;
        }

        $primaryKey = (array)$this->_table->primaryKey();
        $key = $entity->get(current($primaryKey));

        foreach ($translations as $lang => $translation) {
            if (!$translation->id) {
                $update = [
                    'id' => $key,
                    'locale' => $lang,
                ];
                $translation->set($update, ['setter' => false]);
            }
        }

        $entity->set('_i18n', $translations);
    }

    /**
     * Based on the passed config, return the translation table instance
     *
     * If the table already exists in the registry - don't pass any config
     * as that'll just lead to an exception trying to reconfigure an existing
     * table.
     *
     * @param array $config behavior config to use
     * @return \Cake\ORM\Table Translation table instance
     */
    protected function _translationTable($config = [])
    {
        if (!$config) {
            $config = $this->config();
        }

        if (TableRegistry::exists($config['alias'])) {
            return TableRegistry::get($config['alias']);
        }

        return TableRegistry::get(
            $config['alias'],
            ['table' => $config['translationTable']]
        );
    }

    /**
     * Lazy define and return the main table fields
     *
     * @return array
     */
    protected function _mainFields()
    {
        $fields = $this->config('mainTableFields');

        if ($fields) {
            return $fields;
        }

        $table = $this->_table;
        $fields = $table->schema()->columns();

        $this->config('mainTableFields', $fields);

        return $fields;
    }

    /**
     * Lazy define and return the translation table fields
     *
     * @return array
     */
    protected function _translationFields()
    {
        $fields = $this->config('fields');

        if ($fields) {
            return $fields;
        }

        $table = $this->_translationTable();
        $fields = $table->schema()->columns();
        $fields = array_values(array_diff($fields, ['id', 'locale']));

        $this->config('fields', $fields);

        return $fields;
    }
}
