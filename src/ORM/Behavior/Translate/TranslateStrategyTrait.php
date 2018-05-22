<?php
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

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\ORM\Table;

/**
 * Contains common code needed by TranslateBehavior strategy classes.
 */
trait TranslateStrategyTrait
{

    /**
     * Table instance
     *
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * The locale name that will be used to override fields in the bound table
     * from the translations table
     *
     * @var string
     */
    protected $locale;

    /**
     * Instance of Table responsible for translating
     *
     * @var \Cake\ORM\Table
     */
    protected $translationTable;

    /**
     * Return translation table instance.
     *
     * @return \Cake\ORM\Table
     */
    public function getTranslationTable(): Table
    {
        return $this->translationTable;
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
     * in order to unset the current locale, and to make the behavior fall back to using the
     * globally configured locale.
     * @return $this
     * @see \Cake\ORM\Behavior\TranslateBehavior::getLocale()
     * @link https://book.cakephp.org/3.0/en/orm/behaviors/translate.html#retrieving-one-language-without-using-i18n-locale
     * @link https://book.cakephp.org/3.0/en/orm/behaviors/translate.html#saving-in-another-language
     */
    public function setLocale(?string $locale)
    {
        $this->locale = $locale;

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
        return $this->locale ?: I18n::getLocale();
    }

    /**
     * Unset empty translations to avoid persistence.
     *
     * Should only be called if $this->_config['allowEmptyTranslations'] is false.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to check for empty translations fields inside.
     * @return void
     */
    protected function unsetEmptyFields($entity)
    {
        $translations = (array)$entity->get('_translations');
        foreach ($translations as $locale => $translation) {
            $fields = $translation->extract($this->_config['fields'], false);
            foreach ($fields as $field => $value) {
                if (strlen($value) === 0) {
                    $translation->unsetProperty($field);
                }
            }

            $translation = $translation->extract($this->_config['fields']);

            // If now, the current locale property is empty,
            // unset it completely.
            if (empty(array_filter($translation))) {
                unset($entity->get('_translations')[$locale]);
            }
        }

        // If now, the whole _translations property is empty,
        // unset it completely and return
        if (empty($entity->get('_translations'))) {
            $entity->unsetProperty('_translations');
        }
    }

    /**
     * Build a set of properties that should be included in the marshalling process.

     * Add in `_translations` marshalling handlers. You can disable marshalling
     * of translations by setting `'translations' => false` in the options
     * provided to `Table::newEntity()` or `Table::patchEntity()`.
     *
     * @param \Cake\ORM\Marshaller $marshaller The marhshaller of the table the behavior is attached to.
     * @param array $map The property map being built.
     * @param array $options The options array used in the marshalling call.
     * @return array A map of `[property => callable]` of additional properties to marshal.
     */
    public function buildMarshalMap($marshaller, $map, $options)
    {
        if (isset($options['translations']) && !$options['translations']) {
            return [];
        }

        return [
            '_translations' => function ($value, $entity) use ($marshaller, $options) {
                /* @var \Cake\Datasource\EntityInterface $entity */
                $translations = $entity->get('_translations');
                foreach ($this->_config['fields'] as $field) {
                    $options['validate'] = $this->_config['validator'];
                    $errors = [];
                    if (!is_array($value)) {
                        return null;
                    }
                    foreach ($value as $language => $fields) {
                        if (!isset($translations[$language])) {
                            $translations[$language] = $this->table->newEntity();
                        }
                        $marshaller->merge($translations[$language], $fields, $options);
                        if ((bool)$translations[$language]->getErrors()) {
                            $errors[$language] = $translations[$language]->getErrors();
                        }
                    }
                    // Set errors into the root entity, so validation errors
                    // match the original form data position.
                    $entity->setErrors($errors);
                }

                return $translations;
            },
        ];
    }

    /**
     * Unsets the temporary `_i18n` property after the entity has been saved
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity): void
    {
        $entity->unsetProperty('_i18n');
    }
}
