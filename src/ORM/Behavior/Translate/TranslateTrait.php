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
namespace Cake\ORM\Behavior\Translate;

use Cake\Datasource\EntityInterface;

/**
 * Contains translation methods aimed to help managing multiple translations
 * for an entity.
 *
 * @require-implements \Cake\Datasource\EntityInterface
 */
trait TranslateTrait
{
    /**
     * Checks whether a translation exists for the given language.
     *
     * @param string $language Language to check.
     * @return bool
     */
    public function hasTranslation(string $language): bool
    {
        $i18n = $this->get('_translations');

        return isset($i18n[$language]) && $i18n[$language] instanceof EntityInterface;
    }

    /**
     * Returns the entity containing the translated fields for this object and for
     * the specified language, or null if the translation does not exist.
     *
     * @param string $language Language to return entity for.
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function translation(string $language): ?EntityInterface
    {
        $i18n = $this->get('_translations');

        if (isset($i18n[$language]) && $i18n[$language] instanceof EntityInterface) {
            return $i18n[$language];
        }

        return null;
    }

    /**
     * Returns the entity containing the translated fields for this object and for
     * the specified language. If the translation for the passed language does not
     * exist, a new empty entity will be created.
     *
     * This method marks `_translations` as dirty to facilitate saving.
     *
     * @param string $language Language to return entity for.
     * @return \Cake\Datasource\EntityInterface
     */
    public function getOrCreateTranslation(string $language): EntityInterface
    {
        $i18n = $this->get('_translations') ?? [];

        if (!isset($i18n[$language]) || !($i18n[$language] instanceof EntityInterface)) {
            $i18n[$language] = new static();
            $this->set('_translations', $i18n);
        }

        $this->setDirty('_translations', true);

        return $i18n[$language];
    }
}
