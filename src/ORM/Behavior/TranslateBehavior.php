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
namespace Cake\ORM\Behavior;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

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
class TranslateBehavior extends Behavior implements PropertyMarshalInterface
{

    use LocatorAwareTrait;

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
     * Instance of Table responsible for translating
     *
     * @var \Cake\ORM\Table
     */
    protected $_translationTable;

    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'implementedFinders' => ['translations' => 'findTranslations'],
        'implementedMethods' => ['locale' => 'locale'],
        'fields' => [],
        'translationTable' => 'I18n',
        'defaultLocale' => '',
        'referenceName' => '',
        'allowEmptyTranslations' => true,
        'onlyTranslated' => false,
        'strategy' => 'subquery',
        'tableLocator' => null,
        'validator' => false
    ];

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        $config += [
            'defaultLocale' => I18n::defaultLocale(),
            'referenceName' => $this->_referenceName($table)
        ];

        if (isset($config['tableLocator'])) {
            $this->_tableLocator = $config['tableLocator'];
        }

        parent::__construct($table, $config);
    }

    /**
     * Initialize hook
     *
     * @param array $config The config for this behavior.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->_translationTable = $this->tableLocator()->get($this->_config['translationTable']);

        $this->setupFieldAssociations(
            $this->_config['fields'],
            $this->_config['translationTable'],
            $this->_config['referenceName'],
            $this->_config['strategy']
        );
    }

    /**
     * Creates the associations between the bound table and every field passed to
     * this method.
     *
     * Additionally it creates a `i18n` HasMany association that will be
     * used for fetching all translations for each record in the bound table
     *
     * @param array $fields list of fields to create associations for
     * @param string $table the table name to use for storing each field translation
     * @param string $model the model field value
     * @param string $strategy the strategy used in the _i18n association
     *
     * @return void
     */
    public function setupFieldAssociations($fields, $table, $model, $strategy)
    {
        $targetAlias = $this->_translationTable->alias();
        $alias = $this->_table->alias();
        $filter = $this->_config['onlyTranslated'];
        $tableLocator = $this->tableLocator();

        foreach ($fields as $field) {
            $name = $alias . '_' . $field . '_translation';

            if (!$tableLocator->exists($name)) {
                $fieldTable = $tableLocator->get($name, [
                    'className' => $table,
                    'alias' => $name,
                    'table' => $this->_translationTable->table()
                ]);
            } else {
                $fieldTable = $tableLocator->get($name);
            }

            $conditions = [
                $name . '.model' => $model,
                $name . '.field' => $field,
            ];
            if (!$this->_config['allowEmptyTranslations']) {
                $conditions[$name . '.content !='] = '';
            }

            $this->_table->hasOne($name, [
                'targetTable' => $fieldTable,
                'foreignKey' => 'foreign_key',
                'joinType' => $filter ? 'INNER' : 'LEFT',
                'conditions' => $conditions,
                'propertyName' => $field . '_translation'
            ]);
        }

        $conditions = ["$targetAlias.model" => $model];
        if (!$this->_config['allowEmptyTranslations']) {
            $conditions["$targetAlias.content !="] = '';
        }

        $this->_table->hasMany($targetAlias, [
            'className' => $table,
            'foreignKey' => 'foreign_key',
            'strategy' => $strategy,
            'conditions' => $conditions,
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

        $conditions = function ($field, $locale, $query, $select) {
            return function ($q) use ($field, $locale, $query, $select) {
                $q->where([$q->repository()->aliasField('locale') => $locale]);

                if ($query->autoFields() ||
                    in_array($field, $select, true) ||
                    in_array($this->_table->aliasField($field), $select, true)
                ) {
                    $q->select(['id', 'content']);
                }

                return $q;
            };
        };

        $contain = [];
        $fields = $this->_config['fields'];
        $alias = $this->_table->alias();
        $select = $query->clause('select');

        $changeFilter = isset($options['filterByCurrentLocale']) &&
            $options['filterByCurrentLocale'] !== $this->_config['onlyTranslated'];

        foreach ($fields as $field) {
            $name = $alias . '_' . $field . '_translation';

            $contain[$name]['queryBuilder'] = $conditions(
                $field,
                $locale,
                $query,
                $select
            );

            if ($changeFilter) {
                $filter = $options['filterByCurrentLocale'] ? 'INNER' : 'LEFT';
                $contain[$name]['joinType'] = $filter;
            }
        }

        $query->contain($contain);
        $query->formatResults(function ($results) use ($locale) {
            return $this->_rowMapper($results, $locale);
        }, $query::PREPEND);
    }

    /**
     * Modifies the entity before it is saved so that translated fields are persisted
     * in the database too.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject $options the options passed to the save method
     * @return void
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        $locale = $entity->get('_locale') ?: $this->locale();
        $newOptions = [$this->_translationTable->alias() => ['validate' => false]];
        $options['associated'] = $newOptions + $options['associated'];

        $this->_bundleTranslatedFields($entity);
        $bundled = $entity->get('_i18n') ?: [];
        $noBundled = count($bundled) === 0;

        // No additional translation records need to be saved,
        // as the entity is in the default locale.
        if ($noBundled && $locale === $this->config('defaultLocale')) {
            return;
        }

        $values = $entity->extract($this->_config['fields'], true);
        $fields = array_keys($values);
        $noFields = empty($fields);

        $primaryKey = (array)$this->_table->primaryKey();
        $key = $entity->get(current($primaryKey));

        if ($noFields && $noBundled) {
            return;
        }

        // If there no fields, and bundled translations,
        // we need to mark fields as dirty so the entity persists.
        if ($noFields && $bundled) {
            foreach ($this->_config['fields'] as $field) {
                $entity->dirty($field, true);
            }
            return;
        }

        $model = $this->_config['referenceName'];
        $preexistent = $this->_translationTable->find()
            ->select(['id', 'field'])
            ->where(['field IN' => $fields, 'locale' => $locale, 'foreign_key' => $key, 'model' => $model])
            ->bufferResults(false)
            ->indexBy('field');

        $modified = [];
        foreach ($preexistent as $field => $translation) {
            $translation->set('content', $values[$field]);
            $modified[$field] = $translation;
        }

        $new = array_diff_key($values, $modified);
        foreach ($new as $field => $content) {
            $new[$field] = new Entity(compact('locale', 'field', 'content', 'model'), [
                'useSetters' => false,
                'markNew' => true
            ]);
        }

        $entity->set('_i18n', array_merge($bundled, array_values($modified + $new)));
        $entity->set('_locale', $locale, ['setter' => false]);
        $entity->dirty('_locale', false);

        foreach ($fields as $field) {
            $entity->dirty($field, false);
        }
    }

    /**
     * Unsets the temporary `_i18n` property after the entity has been saved
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity)
    {
        $entity->unsetProperty('_i18n');
    }

    /**
     * Add in _translations marshalling handlers if translation marshalling is
     * enabled. You need to specifically enable translation marshalling by adding
     * `'translations' => true` to the options provided to `Table::newEntity()` or `Table::patchEntity()`.
     *
     * {@inheritDoc}
     */
    public function buildMarshalMap($marshaller, $map, $options)
    {
        if (isset($options['translations']) && !$options['translations']) {
            return [];
        }

        return [
            '_translations' => function ($value, $entity) use ($marshaller, $options) {
                $translations = $entity->get('_translations');
                foreach ($this->_config['fields'] as $field) {
                    $options['validate'] = $this->_config['validator'];
                    $errors = [];
                    if (!is_array($value)) {
                        return;
                    }
                    foreach ($value as $language => $fields) {
                        if (!isset($translations[$language])) {
                            $translations[$language] = $this->_table->newEntity();
                        }
                        $marshaller->merge($translations[$language], $fields, $options);
                        if ((bool)$translations[$language]->errors()) {
                            $errors[$language] = $translations[$language]->errors();
                        }
                    }
                    // Set errors into the root entity, so validation errors
                    // match the original form data position.
                    $entity->errors($errors);
                }

                return $translations;
            }
        ];
    }

    /**
     * Sets all future finds for the bound table to also fetch translated fields for
     * the passed locale. If no value is passed, it returns the currently configured
     * locale
     *
     * @param string|null $locale The locale to use for fetching translated records
     * @return string
     */
    public function locale($locale = null)
    {
        if ($locale === null) {
            return $this->_locale ?: I18n::locale();
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
     * ```
     * $article = $articles->find('translations', ['locales' => ['eng', 'deu'])->first();
     * $englishTranslatedFields = $article->get('_translations')['eng'];
     * ```
     *
     * If the `locales` array is not passed, it will bring all translations found
     * for each record.
     *
     * @param \Cake\ORM\Query $query The original query to modify
     * @param array $options Options
     * @return \Cake\ORM\Query
     */
    public function findTranslations(Query $query, array $options)
    {
        $locales = isset($options['locales']) ? $options['locales'] : [];
        $targetAlias = $this->_translationTable->alias();

        return $query
            ->contain([$targetAlias => function ($q) use ($locales, $targetAlias) {
                if ($locales) {
                    $q->where(["$targetAlias.locale IN" => $locales]);
                }

                return $q;
            }])
            ->formatResults([$this, 'groupTranslations'], $query::PREPEND);
    }

    /**
     * Determine the reference name to use for a given table
     *
     * The reference name is usually derived from the class name of the table object
     * (PostsTable -> Posts), however for autotable instances it is derived from
     * the database table the object points at - or as a last resort, the alias
     * of the autotable instance.
     *
     * @param \Cake\ORM\Table $table The table class to get a reference name for.
     * @return string
     */
    protected function _referenceName(Table $table)
    {
        $name = namespaceSplit(get_class($table));
        $name = substr(end($name), 0, -5);
        if (empty($name)) {
            $name = $table->table() ?: $table->alias();
            $name = Inflector::camelize($name);
        }

        return $name;
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
        return $results->map(function ($row) use ($locale) {
            if ($row === null) {
                return $row;
            }
            $hydrated = !is_array($row);

            foreach ($this->_config['fields'] as $field) {
                $name = $field . '_translation';
                $translation = isset($row[$name]) ? $row[$name] : null;

                if ($translation === null || $translation === false) {
                    unset($row[$name]);
                    continue;
                }

                $content = isset($translation['content']) ? $translation['content'] : null;
                if ($content !== null) {
                    $row[$field] = $content;
                }

                unset($row[$name]);
            }

            $row['_locale'] = $locale;
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
            if (!$row instanceof EntityInterface) {
                return $row;
            }
            $translations = (array)$row->get('_i18n');
            if (empty($translations) && $row->get('_translations')) {
                return $row;
            }
            $grouped = new Collection($translations);

            $result = [];
            foreach ($grouped->combine('field', 'content', 'locale') as $locale => $keys) {
                $entityClass = $this->_table->entityClass();
                $translation = new $entityClass($keys + ['locale' => $locale], [
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

    /**
     * Helper method used to generated multiple translated field entities
     * out of the data found in the `_translations` property in the passed
     * entity. The result will be put into its `_i18n` property
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @return void
     */
    protected function _bundleTranslatedFields($entity)
    {
        $translations = (array)$entity->get('_translations');

        if (empty($translations) && !$entity->dirty('_translations')) {
            return;
        }

        $fields = $this->_config['fields'];
        $primaryKey = (array)$this->_table->primaryKey();
        $key = $entity->get(current($primaryKey));
        $find = [];

        foreach ($translations as $lang => $translation) {
            foreach ($fields as $field) {
                if (!$translation->dirty($field)) {
                    continue;
                }
                $find[] = ['locale' => $lang, 'field' => $field, 'foreign_key' => $key];
                $contents[] = new Entity(['content' => $translation->get($field)], [
                    'useSetters' => false
                ]);
            }
        }

        if (empty($find)) {
            return;
        }

        $results = $this->_findExistingTranslations($find);

        foreach ($find as $i => $translation) {
            if (!empty($results[$i])) {
                $contents[$i]->set('id', $results[$i], ['setter' => false]);
                $contents[$i]->isNew(false);
            } else {
                $translation['model'] = $this->_config['referenceName'];
                $contents[$i]->set($translation, ['setter' => false, 'guard' => false]);
                $contents[$i]->isNew(true);
            }
        }

        $entity->set('_i18n', $contents);
    }

    /**
     * Returns the ids found for each of the condition arrays passed for the translations
     * table. Each records is indexed by the corresponding position to the conditions array
     *
     * @param array $ruleSet an array of arary of conditions to be used for finding each
     * @return array
     */
    protected function _findExistingTranslations($ruleSet)
    {
        $association = $this->_table->association($this->_translationTable->alias());

        $query = $association->find()
            ->select(['id', 'num' => 0])
            ->where(current($ruleSet))
            ->hydrate(false)
            ->bufferResults(false);

        unset($ruleSet[0]);
        foreach ($ruleSet as $i => $conditions) {
            $q = $association->find()
                ->select(['id', 'num' => $i])
                ->where($conditions);
            $query->unionAll($q);
        }

        return $query->combine('num', 'id')->toArray();
    }
}
