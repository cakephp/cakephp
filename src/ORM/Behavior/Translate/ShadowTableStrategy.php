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
use Cake\Collection\CollectionInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Database\Expression\FieldInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Marshaller;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;

/**
 * This class provides a way to translate dynamic data by keeping translations
 * in a separate shadow table where each row corresponds to a row of primary table.
 */
class ShadowTableStrategy implements TranslateStrategyInterface
{
    use InstanceConfigTrait;
    use LocatorAwareTrait;
    use TranslateStrategyTrait {
        buildMarshalMap as private _buildMarshalMap;
    }

    /**
     * Default config
     *
     * These are merged with user-provided configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'fields' => [],
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
     * @param \Cake\ORM\Table $table Table instance.
     * @param array<string, mixed> $config Configuration.
     */
    public function __construct(Table $table, array $config = [])
    {
        $tableAlias = $table->getAlias();
        [$plugin] = pluginSplit($table->getRegistryAlias(), true);
        $tableReferenceName = $config['referenceName'];

        $config += [
            'mainTableAlias' => $tableAlias,
            'translationTable' => $plugin . $tableReferenceName . 'Translations',
            'hasOneAlias' => $tableAlias . 'Translation',
        ];

        if (isset($config['tableLocator'])) {
            $this->_tableLocator = $config['tableLocator'];
        }

        $this->setConfig($config);
        $this->table = $table;
        $this->translationTable = $this->getTableLocator()->get(
            $this->_config['translationTable'],
            ['allowFallbackClass' => true]
        );

        $this->setupAssociations();
    }

    /**
     * Create a hasMany association for all records.
     *
     * Don't create a hasOne association here as the join conditions are modified
     * in before find - so create/modify it there.
     *
     * @return void
     */
    protected function setupAssociations()
    {
        $config = $this->getConfig();

        $targetAlias = $this->translationTable->getAlias();
        $this->table->hasMany($targetAlias, [
            'className' => $config['translationTable'],
            'foreignKey' => 'id',
            'strategy' => $config['strategy'],
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
     * @param \Cake\ORM\Query $query Query.
     * @param \ArrayObject $options The options for the query.
     * @return void
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options)
    {
        $locale = Hash::get($options, 'locale', $this->getLocale());
        $config = $this->getConfig();

        if ($locale === $config['defaultLocale']) {
            return;
        }

        $this->setupHasOneAssociation($locale, $options);

        $fieldsAdded = $this->addFieldsToQuery($query, $config);
        $orderByTranslatedField = $this->iterateClause($query, 'order', $config);
        $filteredByTranslatedField =
            $this->traverseClause($query, 'where', $config) ||
            $config['onlyTranslated'] ||
            ($options['filterByCurrentLocale'] ?? null);

        if (!$fieldsAdded && !$orderByTranslatedField && !$filteredByTranslatedField) {
            return;
        }

        $query->contain([$config['hasOneAlias']]);

        $query->formatResults(function ($results) use ($locale) {
            return $this->rowMapper($results, $locale);
        }, $query::PREPEND);
    }

    /**
     * Create a hasOne association for record with required locale.
     *
     * @param string $locale Locale
     * @param \ArrayObject $options Find options
     * @return void
     */
    protected function setupHasOneAssociation(string $locale, ArrayObject $options): void
    {
        $config = $this->getConfig();

        [$plugin] = pluginSplit($config['translationTable']);
        $hasOneTargetAlias = $plugin ? ($plugin . '.' . $config['hasOneAlias']) : $config['hasOneAlias'];
        if (!$this->getTableLocator()->exists($hasOneTargetAlias)) {
            // Load table before hand with fallback class usage enabled
            $this->getTableLocator()->get(
                $hasOneTargetAlias,
                [
                    'className' => $config['translationTable'],
                    'allowFallbackClass' => true,
                ]
            );
        }

        if (isset($options['filterByCurrentLocale'])) {
            $joinType = $options['filterByCurrentLocale'] ? 'INNER' : 'LEFT';
        } else {
            $joinType = $config['onlyTranslated'] ? 'INNER' : 'LEFT';
        }

        $this->table->hasOne($config['hasOneAlias'], [
            'foreignKey' => ['id'],
            'joinType' => $joinType,
            'propertyName' => 'translation',
            'className' => $config['translationTable'],
            'conditions' => [
                $config['hasOneAlias'] . '.locale' => $locale,
            ],
        ]);
    }

    /**
     * Add translation fields to query.
     *
     * If the query is using autofields (directly or implicitly) add the
     * main table's fields to the query first.
     *
     * Only add translations for fields that are in the main table, always
     * add the locale field though.
     *
     * @param \Cake\ORM\Query $query The query to check.
     * @param array<string, mixed> $config The config to use for adding fields.
     * @return bool Whether a join to the translation table is required.
     */
    protected function addFieldsToQuery($query, array $config)
    {
        if ($query->isAutoFieldsEnabled()) {
            return true;
        }

        $select = array_filter($query->clause('select'), function ($field) {
            return is_string($field);
        });

        if (!$select) {
            return true;
        }

        $alias = $config['mainTableAlias'];
        $joinRequired = false;
        foreach ($this->translatedFields() as $field) {
            if (array_intersect($select, [$field, "$alias.$field"])) {
                $joinRequired = true;
                $query->select($query->aliasField($field, $config['hasOneAlias']));
            }
        }

        if ($joinRequired) {
            $query->select($query->aliasField('locale', $config['hasOneAlias']));
        }

        return $joinRequired;
    }

    /**
     * Iterate over a clause to alias fields.
     *
     * The objective here is to transparently prevent ambiguous field errors by
     * prefixing fields with the appropriate table alias. This method currently
     * expects to receive an order clause only.
     *
     * @param \Cake\ORM\Query $query the query to check.
     * @param string $name The clause name.
     * @param array<string, mixed> $config The config to use for adding fields.
     * @return bool Whether a join to the translation table is required.
     */
    protected function iterateClause($query, $name = '', $config = []): bool
    {
        $clause = $query->clause($name);
        if (!$clause || !$clause->count()) {
            return false;
        }

        $alias = $config['hasOneAlias'];
        $fields = $this->translatedFields();
        $mainTableAlias = $config['mainTableAlias'];
        $mainTableFields = $this->mainFields();
        $joinRequired = false;

        $clause->iterateParts(
            function ($c, &$field) use ($fields, $alias, $mainTableAlias, $mainTableFields, &$joinRequired) {
                if (!is_string($field) || strpos($field, '.')) {
                    return $c;
                }

                /** @psalm-suppress ParadoxicalCondition */
                if (in_array($field, $fields, true)) {
                    $joinRequired = true;
                    $field = "$alias.$field";
                } elseif (in_array($field, $mainTableFields, true)) {
                    $field = "$mainTableAlias.$field";
                }

                return $c;
            }
        );

        return $joinRequired;
    }

    /**
     * Traverse over a clause to alias fields.
     *
     * The objective here is to transparently prevent ambiguous field errors by
     * prefixing fields with the appropriate table alias. This method currently
     * expects to receive a where clause only.
     *
     * @param \Cake\ORM\Query $query the query to check.
     * @param string $name The clause name.
     * @param array<string, mixed> $config The config to use for adding fields.
     * @return bool Whether a join to the translation table is required.
     */
    protected function traverseClause($query, $name = '', $config = []): bool
    {
        $clause = $query->clause($name);
        if (!$clause || !$clause->count()) {
            return false;
        }

        $alias = $config['hasOneAlias'];
        $fields = $this->translatedFields();
        $mainTableAlias = $config['mainTableAlias'];
        $mainTableFields = $this->mainFields();
        $joinRequired = false;

        $clause->traverse(
            function ($expression) use ($fields, $alias, $mainTableAlias, $mainTableFields, &$joinRequired) {
                if (!($expression instanceof FieldInterface)) {
                    return;
                }
                $field = $expression->getField();
                if (!is_string($field) || strpos($field, '.')) {
                    return;
                }

                if (in_array($field, $fields, true)) {
                    $joinRequired = true;
                    $expression->setField("$alias.$field");

                    return;
                }

                /** @psalm-suppress ParadoxicalCondition */
                if (in_array($field, $mainTableFields, true)) {
                    $expression->setField("$mainTableAlias.$field");
                }
            }
        );

        return $joinRequired;
    }

    /**
     * Modifies the entity before it is saved so that translated fields are persisted
     * in the database too.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved.
     * @param \ArrayObject $options the options passed to the save method.
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

        $values = $entity->extract($this->translatedFields(), true);
        $fields = array_keys($values);
        $noFields = empty($fields);

        // If there are no fields and no bundled translations, or both fields
        // in the default locale and bundled translations we can
        // skip the remaining logic as its not necessary.
        if ($noFields && $noBundled || ($fields && $bundled)) {
            return;
        }

        $primaryKey = (array)$this->table->getPrimaryKey();
        $id = $entity->get(current($primaryKey));

        // When we have no key and bundled translations, we
        // need to mark the entity dirty so the root
        // entity persists.
        if ($noFields && $bundled && !$id) {
            foreach ($this->translatedFields() as $field) {
                $entity->setDirty($field, true);
            }

            return;
        }

        if ($noFields) {
            return;
        }

        $where = ['locale' => $locale];
        $translation = null;
        if ($id) {
            $where['id'] = $id;

            /** @var \Cake\Datasource\EntityInterface|null $translation */
            $translation = $this->translationTable->find()
                ->select(array_merge(['id', 'locale'], $fields))
                ->where($where)
                ->disableBufferedResults()
                ->first();
        }

        if ($translation) {
            $translation->set($values);
        } else {
            $translation = $this->translationTable->newEntity(
                $where + $values,
                [
                    'useSetters' => false,
                    'markNew' => true,
                ]
            );
        }

        $entity->set('_i18n', array_merge($bundled, [$translation]));
        $entity->set('_locale', $locale, ['setter' => false]);
        $entity->setDirty('_locale', false);

        foreach ($fields as $field) {
            $entity->setDirty($field, false);
        }
    }

    /**
     * @inheritDoc
     */
    public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array
    {
        $this->translatedFields();

        return $this->_buildMarshalMap($marshaller, $map, $options);
    }

    /**
     * Returns a fully aliased field name for translated fields.
     *
     * If the requested field is configured as a translation field, field with
     * an alias of a corresponding association is returned. Table-aliased
     * field name is returned for all other fields.
     *
     * @param string $field Field name to be aliased.
     * @return string
     */
    public function translationField(string $field): string
    {
        if ($this->getLocale() === $this->getConfig('defaultLocale')) {
            return $this->table->aliasField($field);
        }

        $translatedFields = $this->translatedFields();
        if (in_array($field, $translatedFields, true)) {
            return $this->getConfig('hasOneAlias') . '.' . $field;
        }

        return $this->table->aliasField($field);
    }

    /**
     * Modifies the results from a table find in order to merge the translated
     * fields into each entity for a given locale.
     *
     * @param \Cake\Datasource\ResultSetInterface $results Results to map.
     * @param string $locale Locale string
     * @return \Cake\Collection\CollectionInterface
     */
    protected function rowMapper($results, $locale)
    {
        $allowEmpty = $this->_config['allowEmptyTranslations'];

        return $results->map(function ($row) use ($allowEmpty, $locale) {
            /** @var \Cake\Datasource\EntityInterface|array|null $row */
            if ($row === null) {
                return $row;
            }

            $hydrated = !is_array($row);

            if (empty($row['translation'])) {
                $row['_locale'] = $locale;
                unset($row['translation']);

                if ($hydrated) {
                    /** @psalm-suppress PossiblyInvalidMethodCall */
                    $row->clean();
                }

                return $row;
            }

            /** @var \Cake\ORM\Entity|array $translation */
            $translation = $row['translation'];

            /**
             * @psalm-suppress PossiblyInvalidMethodCall
             * @psalm-suppress PossiblyInvalidArgument
             */
            $keys = $hydrated ? $translation->getVisible() : array_keys($translation);

            foreach ($keys as $field) {
                if ($field === 'locale') {
                    $row['_locale'] = $translation[$field];
                    continue;
                }

                if ($translation[$field] !== null) {
                    if ($allowEmpty || $translation[$field] !== '') {
                        $row[$field] = $translation[$field];
                    }
                }
            }

            unset($row['translation']);

            if ($hydrated) {
                /** @psalm-suppress PossiblyInvalidMethodCall */
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
            if (!($row instanceof EntityInterface)) {
                return $row;
            }
            $translations = (array)$row->get('_i18n');
            if (empty($translations) && $row->get('_translations')) {
                return $row;
            }

            $result = [];
            foreach ($translations as $translation) {
                unset($translation['id']);
                $result[$translation['locale']] = $translation;
            }

            $row['_translations'] = $result;
            unset($row['_i18n']);
            if ($row instanceof EntityInterface) {
                $row->clean();
            }

            return $row;
        });
    }

    /**
     * Helper method used to generated multiple translated field entities
     * out of the data found in the `_translations` property in the passed
     * entity. The result will be put into its `_i18n` property.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    protected function bundleTranslatedFields($entity)
    {
        $translations = (array)$entity->get('_translations');

        if (empty($translations) && !$entity->isDirty('_translations')) {
            return;
        }

        $primaryKey = (array)$this->table->getPrimaryKey();
        $key = $entity->get(current($primaryKey));

        foreach ($translations as $lang => $translation) {
            if (!$translation->id) {
                $update = [
                    'id' => $key,
                    'locale' => $lang,
                ];
                $translation->set($update, ['guard' => false]);
            }
        }

        $entity->set('_i18n', $translations);
    }

    /**
     * Lazy define and return the main table fields.
     *
     * @return array<string>
     */
    protected function mainFields()
    {
        $fields = $this->getConfig('mainTableFields');

        if ($fields) {
            return $fields;
        }

        $fields = $this->table->getSchema()->columns();

        $this->setConfig('mainTableFields', $fields);

        return $fields;
    }

    /**
     * Lazy define and return the translation table fields.
     *
     * @return array<string>
     */
    protected function translatedFields()
    {
        $fields = $this->getConfig('fields');

        if ($fields) {
            return $fields;
        }

        $table = $this->translationTable;
        $fields = $table->getSchema()->columns();
        $fields = array_values(array_diff($fields, ['id', 'locale']));

        $this->setConfig('fields', $fields);

        return $fields;
    }
}
