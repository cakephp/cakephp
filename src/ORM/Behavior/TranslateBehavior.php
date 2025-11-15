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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Behavior;

use ArrayObject;
use Cake\Datasource\QueryInterface;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\ORM\Behavior;
use Cake\ORM\Behavior\Translate\ShadowTableStrategy;
use Cake\ORM\Behavior\Translate\TranslateStrategyInterface;
use Cake\ORM\Marshaller;
use Cake\ORM\PropertyMarshalInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use function Cake\Core\namespaceSplit;

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
    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'implementedFinders' => ['translations' => 'findTranslations'],
        'implementedMethods' => [
            'setLocale' => 'setLocale',
            'getLocale' => 'getLocale',
            'translationField' => 'translationField',
            'getStrategy' => 'getStrategy',
        ],
        'fields' => [],
        'defaultLocale' => null,
        'referenceName' => '',
        'allowEmptyTranslations' => true,
        'onlyTranslated' => false,
        'strategy' => 'subquery',
        'tableLocator' => null,
        'validator' => false,
        'strategyClass' => null,
    ];

    /**
     * Default strategy class name.
     *
     * @var string
     * @phpstan-var class-string<\Cake\ORM\Behavior\Translate\TranslateStrategyInterface>
     */
    protected static string $defaultStrategyClass = ShadowTableStrategy::class;

    /**
     * Translation strategy instance.
     *
     * @var \Cake\ORM\Behavior\Translate\TranslateStrategyInterface|null
     */
    protected ?TranslateStrategyInterface $strategy = null;

    /**
     * Constructor
     *
     * ### Options
     *
     * - `fields`: List of fields which need to be translated. Providing this fields
     *   list is mandatory when using `EavStrategy`. If the fields list is empty when
     *   using `ShadowTableStrategy` then the list will be auto generated based on
     *   shadow table schema.
     * - `defaultLocale`: The locale which is treated as default by the behavior.
     *   Fields values for default locale will be stored in the primary table itself
     *   and the rest in translation table. If not explicitly set the value of
     *   `I18n::getDefaultLocale()` will be used to get default locale.
     *   If you do not want any default locale and want translated fields
     *   for all locales to be stored in translation table then set this config
     *   to empty string `''`.
     * - `allowEmptyTranslations`: By default if a record has been translated and
     *   stored as an empty string the translate behavior will take and use this
     *   value to overwrite the original field value. If you don't want this behavior
     *   then set this option to `false`.
     * - `validator`: The validator that should be used when translation records
     *   are created/modified. Default `null`.
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array<string, mixed> $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        $config += [
            'defaultLocale' => I18n::getDefaultLocale(),
            'referenceName' => $this->referenceName($table),
            'tableLocator' => $table->associations()->getTableLocator(),
        ];

        parent::__construct($table, $config);
    }

    /**
     * Initialize hook
     *
     * @param array<string, mixed> $config The config for this behavior.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->getStrategy();
    }

    /**
     * Set default strategy class name.
     *
     * @param string $class Class name.
     * @return void
     * @since 4.0.0
     * @phpstan-param class-string<\Cake\ORM\Behavior\Translate\TranslateStrategyInterface> $class
     */
    public static function setDefaultStrategyClass(string $class): void
    {
        static::$defaultStrategyClass = $class;
    }

    /**
     * Get default strategy class name.
     *
     * @return string
     * @since 4.0.0
     * @phpstan-return class-string<\Cake\ORM\Behavior\Translate\TranslateStrategyInterface>
     */
    public static function getDefaultStrategyClass(): string
    {
        return static::$defaultStrategyClass;
    }

    /**
     * Get strategy class instance.
     *
     * @return \Cake\ORM\Behavior\Translate\TranslateStrategyInterface
     * @since 4.0.0
     */
    public function getStrategy(): TranslateStrategyInterface
    {
        return $this->strategy ??= $this->createStrategy();
    }

    /**
     * Create strategy instance.
     *
     * @return \Cake\ORM\Behavior\Translate\TranslateStrategyInterface
     * @since 4.0.0
     */
    protected function createStrategy(): TranslateStrategyInterface
    {
        $config = array_diff_key(
            $this->_config,
            ['implementedFinders', 'implementedMethods', 'strategyClass'],
        );
        /** @var class-string<\Cake\ORM\Behavior\Translate\TranslateStrategyInterface> $className */
        $className = $this->getConfig('strategyClass', static::$defaultStrategyClass);

        return new $className($this->_table, $config);
    }

    /**
     * Set strategy class instance.
     *
     * @param \Cake\ORM\Behavior\Translate\TranslateStrategyInterface $strategy Strategy class instance.
     * @return $this
     * @since 4.0.0
     */
    public function setStrategy(TranslateStrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Gets the Model callbacks this behavior is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
        ];
    }

    /**
     * Hoist fields for the default locale under `_translations` key to the root
     * in the data.
     *
     * This allows `_translations.{locale}.field_name` type naming even for the
     * default locale in forms.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \ArrayObject $data
     * @param \ArrayObject $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($options['translations']) && !$options['translations']) {
            return;
        }

        $defaultLocale = $this->getConfig('defaultLocale');
        if (!isset($data['_translations'][$defaultLocale])) {
            return;
        }

        foreach ($data['_translations'][$defaultLocale] as $field => $value) {
            $data[$field] = $value;
        }

        unset($data['_translations'][$defaultLocale]);
    }

    /**
     * {@inheritDoc}
     *
     * Add in `_translations` marshaling handlers. You can disable marshaling
     * of translations by setting `'translations' => false` in the options
     * provided to `Table::newEntity()` or `Table::patchEntity()`.
     *
     * @param \Cake\ORM\Marshaller $marshaller The marshaler of the table the behavior is attached to.
     * @param array<string, callable> $map The property map being built.
     * @param array<string, mixed> $options The options array used in the marshaling call.
     * @return array<string, callable> A map of `[property => callable]` of additional properties to marshal.
     */
    public function buildMarshalMap(Marshaller $marshaller, array $map, array $options): array
    {
        return $this->getStrategy()->buildMarshalMap($marshaller, $map, $options);
    }

    /**
     * Sets the locale that should be used for all future find and save operations on
     * the table where this behavior is attached to.
     *
     * When fetching records, the behavior will include the content for the locale set
     * via this method, and likewise when saving data, it will save the data in that
     * locale.
     *
     * Note that in case an entity has a `_locale` property set, that locale will win
     * over the locale set via this method (and over the globally configured one for
     * that matter)!
     *
     * @param string|null $locale The locale to use for fetching and saving records. Pass `null`
     * in order to unset the current locale, and to make the behavior falls back to using the
     * globally configured locale.
     * @return $this
     * @see \Cake\ORM\Behavior\TranslateBehavior::getLocale()
     * @link https://book.cakephp.org/5/en/orm/behaviors/translate.html#retrieving-one-language-without-using-i18n-locale
     * @link https://book.cakephp.org/5/en/orm/behaviors/translate.html#saving-in-another-language
     */
    public function setLocale(?string $locale)
    {
        $this->getStrategy()->setLocale($locale);

        return $this;
    }

    /**
     * Returns the current locale.
     *
     * If no locale has been explicitly set via `setLocale()`, this method will return
     * the currently configured global locale.
     *
     * @return string
     * @see \Cake\I18n\I18n::getLocale()
     * @see \Cake\ORM\Behavior\TranslateBehavior::setLocale()
     */
    public function getLocale(): string
    {
        return $this->getStrategy()->getLocale();
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
        return $this->getStrategy()->translationField($field);
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
     * $article = $articles->find('translations', locales: ['eng', 'deu'])->first();
     * $englishTranslatedFields = $article->get('_translations')['eng'];
     * ```
     *
     * If the `locales` array is not passed, it will bring all translations found
     * for each record.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The original query to modify
     * @param array<string> $locales A list of locales or options with the `locales` key defined
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findTranslations(SelectQuery $query, array $locales = []): SelectQuery
    {
        $targetAlias = $this->getStrategy()->getTranslationTable()->getAlias();

        return $query
            ->contain([$targetAlias => function (QueryInterface $query) use ($locales, $targetAlias) {
                if ($locales) {
                    $query->where(["{$targetAlias}.locale IN" => $locales]);
                }

                return $query;
            }])
            ->formatResults($this->getStrategy()->groupTranslations(...), $query::PREPEND);
    }

    /**
     * Proxy method calls to strategy class instance.
     *
     * @param string $method Method name.
     * @param array $args Method arguments.
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->getStrategy()->{$method}(...$args);
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
    protected function referenceName(Table $table): string
    {
        $name = namespaceSplit($table::class);
        $name = substr(end($name), 0, -5);
        if (!$name) {
            $name = $table->getTable() ?: $table->getAlias();
            $name = Inflector::camelize($name);
        }

        return $name;
    }
}
