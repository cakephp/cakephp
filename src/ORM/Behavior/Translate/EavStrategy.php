<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Behavior\Translate;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;

/**
 * This class provides a way to translate dynamic data by keeping translations
 * in a separate table linked to the original record from another one. Translated
 * fields can be configured to override those in the main table when fetched or
 * put aside into another property for the same entity.
 *
 * If you wish to override fields, you need to call the `locale` method in this
 * behavior for setting the language you want to fetch from the translations table.
 *
 * If you want to bring all or certain languages for each of the fetched records,
 * you can use the custom `translations` finder of `TranslateBehavior` that is
 * exposed to the table.
 */
class EavStrategy implements TranslateStrategyInterface
{
    use InstanceConfigTrait;
    use LocatorAwareTrait;
    use TranslateStrategyTrait;

    /**
     * Default config
     *
     * These are merged with user-provided configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'fields' => [],
        'translationTable' => 'I18n',
        'defaultLocale' => null,
        'referenceName' => null,
        'allowEmptyTranslations' => true,
        'onlyTranslated' => false,
        'strategy' => 'subquery',
        'tableLocator' => null,
        'validator' => false,
    ];

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this strategy is attached to.
     * @param array $config The config for this strategy.
     */
    public function __construct(Table $table, array $config = [])
    {
        if (isset($config['tableLocator'])) {
            $this->_tableLocator = $config['tableLocator'];
        }

        $this->setConfig($config);
        $this->table = $table;
        $this->translationTable = $this->getTableLocator()->get($this->_config['translationTable']);

        $this->setupAssociations();
    }

    /**
     * Creates the associations between the bound table and every field passed to
     * this method.
     *
     * Additionally it creates a `i18n` HasMany association that will be
     * used for fetching all translations for each record in the bound table.
     *
     * @return void
     */
    protected function setupAssociations()
    {
        $fields = $this->_config['fields'];
        $table = $this->_config['translationTable'];
        $model = $this->_config['referenceName'];
        $strategy = $this->_config['strategy'];
        $filter = $this->_config['onlyTranslated'];

        $targetAlias = $this->translationTable->getAlias();
        $alias = $this->table->getAlias();
        $tableLocator = $this->getTableLocator();

        foreach ($fields as $field) {
            $name = $alias . '_' . $field . '_translation';

            if (!$tableLocator->exists($name)) {
                $fieldTable = $tableLocator->get($name, [
                    'className' => $table,
                    'alias' => $name,
                    'table' => $this->translationTable->getTable(),
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

            $this->table->hasOne($name, [
                'targetTable' => $fieldTable,
                'foreignKey' => 'foreign_key',
                'joinType' => $filter ? Query::JOIN_TYPE_INNER : Query::JOIN_TYPE_LEFT,
                'conditions' => $conditions,
                'propertyName' => $field . '_translation',
            ]);
        }

        $conditions = ["$targetAlias.model" => $model];
        if (!$this->_config['allowEmptyTranslations']) {
            $conditions["$targetAlias.content !="] = '';
        }

        $this->table->hasMany($targetAlias, [
            'className' => $table,
            'foreignKey' => 'foreign_key',
            'strategy' => $strategy,
            'conditions' => $conditions,
            'propertyName' => '_i18n',
            'dependent' => true,
        ]);
    }

    /**
     * Callback method that listens to the `beforeFind` event in the bound
     * table. It modifies the passed query by eager loading the translated fields
     * and adding a formatter to copy the values into the main table records.
     *
     * @param \Cake\Event\EventInterface $event The beforeFind event that was fired.
     * @param \Cake\ORM\Query $query Query
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options)
    {
        $locale = Hash::get($options, 'locale', $this->getLocale());

        if ($locale === $this->getConfig('defaultLocale')) {
            return;
        }

        $conditions = function ($field, $locale, $query, $select) {
            return function ($q) use ($field, $locale, $query, $select) {
                $q->where([$q->getRepository()->aliasField('locale') => $locale]);

                if (
                    $query->isAutoFieldsEnabled() ||
                    in_array($field, $select, true) ||
                    in_array($this->table->aliasField($field), $select, true)
                ) {
                    $q->select(['id', 'content']);
                }

                return $q;
            };
        };

        $contain = [];
        $fields = $this->_config['fields'];
        $alias = $this->table->getAlias();
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
                $filter = $options['filterByCurrentLocale']
                    ? Query::JOIN_TYPE_INNER
                    : Query::JOIN_TYPE_LEFT;
                $contain[$name]['joinType'] = $filter;
            }
        }

        $query->contain($contain);
        $query->formatResults(function ($results) use ($locale) {
            return $this->rowMapper($results, $locale);
        }, $query::PREPEND);
    }

    /**
     * Modifies the entity before it is saved so that translated fields are persisted
     * in the database too.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject $options the options passed to the save method
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        $locale = $entity->get('_locale') ?: $this->getLocale();
        $newOptions = [$this->translationTable->getAlias() => ['validate' => false]];
        $options['associated'] = $newOptions + $options['associated'];

        // Check early if empty translations are present in the entity.
        // If this is the case, unset them to prevent persistence.
        // This only applies if $this->_config['allowEmptyTranslations'] is false
        if ($this->_config['allowEmptyTranslations'] === false) {
            $this->unsetEmptyFields($entity);
        }

        $this->bundleTranslatedFields($entity);
        $bundled = $entity->get('_i18n') ?: [];
        $noBundled = count($bundled) === 0;

        // No additional translation records need to be saved,
        // as the entity is in the default locale.
        if ($noBundled && $locale === $this->getConfig('defaultLocale')) {
            return;
        }

        $values = $entity->extract($this->_config['fields'], true);
        $fields = array_keys($values);
        $noFields = empty($fields);

        // If there are no fields and no bundled translations, or both fields
        // in the default locale and bundled translations we can
        // skip the remaining logic as its not necessary.
        if ($noFields && $noBundled || ($fields && $bundled)) {
            return;
        }

        $primaryKey = (array)$this->table->getPrimaryKey();
        $key = $entity->get(current($primaryKey));

        // When we have no key and bundled translations, we
        // need to mark the entity dirty so the root
        // entity persists.
        if ($noFields && $bundled && !$key) {
            foreach ($this->_config['fields'] as $field) {
                $entity->setDirty($field, true);
            }

            return;
        }

        if ($noFields) {
            return;
        }

        $model = $this->_config['referenceName'];

        $preexistent = [];
        if ($key) {
            /** @psalm-suppress UndefinedClass */
            $preexistent = $this->translationTable->find()
                ->select(['id', 'field'])
                ->where([
                    'field IN' => $fields,
                    'locale' => $locale,
                    'foreign_key' => $key,
                    'model' => $model,
                ])
                ->disableBufferedResults()
                ->all()
                ->indexBy('field');
        }

        $modified = [];
        foreach ($preexistent as $field => $translation) {
            $translation->set('content', $values[$field]);
            $modified[$field] = $translation;
        }

        $new = array_diff_key($values, $modified);
        foreach ($new as $field => $content) {
            $new[$field] = new Entity(compact('locale', 'field', 'content', 'model'), [
                'useSetters' => false,
                'markNew' => true,
            ]);
        }

        $entity->set('_i18n', array_merge($bundled, array_values($modified + $new)));
        $entity->set('_locale', $locale, ['setter' => false]);
        $entity->setDirty('_locale', false);

        foreach ($fields as $field) {
            $entity->setDirty($field, false);
        }
    }

    /**
     * Returns a fully aliased field name for translated fields.
     *
     * If the requested field is configured as a translation field, the `content`
     * field with an alias of a corresponding association is returned. Table-aliased
     * field name is returned for all other fields.
     *
     * @param string $field Field name to be aliased.
     * @return string
     */
    public function translationField(string $field): string
    {
        $table = $this->table;
        if ($this->getLocale() === $this->getConfig('defaultLocale')) {
            return $table->aliasField($field);
        }
        $associationName = $table->getAlias() . '_' . $field . '_translation';

        if ($table->associations()->has($associationName)) {
            return $associationName . '.content';
        }

        return $table->aliasField($field);
    }

    /**
     * Modifies the results from a table find in order to merge the translated fields
     * into each entity for a given locale.
     *
     * @param \Cake\Datasource\ResultSetInterface $results Results to map.
     * @param string $locale Locale string
     * @return \Cake\Collection\CollectionInterface
     */
    protected function rowMapper($results, $locale)
    {
        return $results->map(function ($row) use ($locale) {
            /** @var \Cake\Datasource\EntityInterface|array|null $row */
            if ($row === null) {
                return $row;
            }
            $hydrated = !is_array($row);

            foreach ($this->_config['fields'] as $field) {
                $name = $field . '_translation';
                $translation = $row[$name] ?? null;

                if ($translation === null || $translation === false) {
                    unset($row[$name]);
                    continue;
                }

                $content = $translation['content'] ?? null;
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
     * Modifies the results from a table find in order to merge full translation
     * records into each entity under the `_translations` key.
     *
     * @param \Cake\Datasource\ResultSetInterface $results Results to modify.
     * @return \Cake\Collection\CollectionInterface
     */
    public function groupTranslations($results): CollectionInterface
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
                $entityClass = $this->table->getEntityClass();
                $translation = new $entityClass($keys + ['locale' => $locale], [
                    'markNew' => false,
                    'useSetters' => false,
                    'markClean' => true,
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
     * entity. The result will be put into its `_i18n` property.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @return void
     */
    protected function bundleTranslatedFields($entity)
    {
        $translations = (array)$entity->get('_translations');

        if (empty($translations) && !$entity->isDirty('_translations')) {
            return;
        }

        $fields = $this->_config['fields'];
        $primaryKey = (array)$this->table->getPrimaryKey();
        $key = $entity->get(current($primaryKey));
        $find = [];
        $contents = [];

        foreach ($translations as $lang => $translation) {
            foreach ($fields as $field) {
                if (!$translation->isDirty($field)) {
                    continue;
                }
                $find[] = ['locale' => $lang, 'field' => $field, 'foreign_key IS' => $key];
                $contents[] = new Entity(['content' => $translation->get($field)], [
                    'useSetters' => false,
                ]);
            }
        }

        if (empty($find)) {
            return;
        }

        $results = $this->findExistingTranslations($find);

        foreach ($find as $i => $translation) {
            if (!empty($results[$i])) {
                $contents[$i]->set('id', $results[$i], ['setter' => false]);
                $contents[$i]->setNew(false);
            } else {
                $translation['model'] = $this->_config['referenceName'];
                $contents[$i]->set($translation, ['setter' => false, 'guard' => false]);
                $contents[$i]->setNew(true);
            }
        }

        $entity->set('_i18n', $contents);
    }

    /**
     * Returns the ids found for each of the condition arrays passed for the
     * translations table. Each records is indexed by the corresponding position
     * to the conditions array.
     *
     * @param array $ruleSet An array of array of conditions to be used for finding each
     * @return array
     */
    protected function findExistingTranslations($ruleSet)
    {
        $association = $this->table->getAssociation($this->translationTable->getAlias());

        $query = $association->find()
            ->select(['id', 'num' => 0])
            ->where(current($ruleSet))
            ->disableHydration()
            ->disableBufferedResults();

        unset($ruleSet[0]);
        foreach ($ruleSet as $i => $conditions) {
            $q = $association->find()
                ->select(['id', 'num' => $i])
                ->where($conditions);
            $query->unionAll($q);
        }

        return $query->all()->combine('num', 'id')->toArray();
    }
}
