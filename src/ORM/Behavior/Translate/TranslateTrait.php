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

use Cake\Datasource\EntityInterface;

/**
 * Contains a translation method aimed to help managing multiple translations
 * for an entity.
 */
trait TranslateTrait
{

    /**
     * Returns the entity containing the translated fields for this object and for
     * the specified language. If the translation for the passed language is not
     * present, a new empty entity will be created so that values can be added to
     * it.
     *
     * @param string $language Language to return entity for.
     * @return $this|\Cake\ORM\Entity
     */
    public function translation($language)
    {
        if ($language === $this->get('_locale')) {
            return $this;
        }

        $i18n = $this->get('_translations');
        $created = false;

        if (empty($i18n)) {
            $i18n = [];
            $created = true;
        }

        if ($created || empty($i18n[$language]) || !($i18n[$language] instanceof EntityInterface)) {
            $className = get_class($this);

            $i18n[$language] = new $className();
            $created = true;
        }

        if ($created) {
            $this->set('_translations', $i18n);
        }

        // Assume the user will modify any of the internal translations, helps with saving
        $this->dirty('_translations', true);

        return $i18n[$language];
    }
}
