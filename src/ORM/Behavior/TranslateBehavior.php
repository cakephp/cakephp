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
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;

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
class TranslateBehavior extends Behavior
{

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
     * Translation engine.
     *
     * @var \Cake\ORM\Behavior\Translate\TranslateInterface
     */
    protected $_engine = null;

    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'engine' => '\Cake\ORM\Behavior\Translate\EavEngine',
        'implementedFinders' => ['translations' => 'findTranslations'],
        'implementedMethods' => ['locale' => 'locale'],
        'fields' => [],
        'translationTable' => 'I18n',
        'defaultLocale' => '',
        'model' => '',
        'onlyTranslated' => false,
        'strategy' => 'subquery',
        'conditions' => ['model' => '']
    ];

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        $config += ['defaultLocale' => I18n::defaultLocale()];
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
        $engine = $this->config('engine');
        $this->_engine = new $engine($this->_table, $this->config());
        $this->_engine->initialize($config);
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
        $this->_engine->beforeFind($event, $query,  $options);
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
        $this->_engine->beforeSave($event, $entity,  $options);
    }

    /**
     * Unsets the temporary `_i18n` property after the entity has been saved
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\ORM\Entity $entity The entity that is going to be saved
     * @return void
     */
    public function afterSave(Event $event, Entity $entity)
    {
        $entity->unsetProperty('_i18n');
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
        return $this->_engine->locale($locale);
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
        return $this->_engine->findTranslations($query, $options);
    }

}
