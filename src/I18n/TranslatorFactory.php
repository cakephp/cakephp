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
 * @since         3.3.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\FormatterInterface;
use Aura\Intl\Package;
use Aura\Intl\TranslatorFactory as BaseTranslatorFactory;
use Aura\Intl\TranslatorInterface;
use RuntimeException;

/**
 * Factory to create translators
 *
 * @internal
 */
class TranslatorFactory extends BaseTranslatorFactory
{
    /**
     * The class to use for new instances.
     *
     * @var string
     */
    protected $class = 'Cake\I18n\Translator';

    /**
     * Returns a new Translator.
     *
     * @param string $locale The locale code for the translator.
     * @param \Aura\Intl\Package $package The Package containing keys and translations.
     * @param \Aura\Intl\FormatterInterface $formatter The formatter to use for interpolating token values.
     * @param \Aura\Intl\TranslatorInterface $fallback A fallback translator to use, if any.
     * @throws \Cake\Core\Exception\Exception If fallback class does not match Cake\I18n\Translator
     * @return \Cake\I18n\Translator
     */
    public function newInstance(
        $locale,
        Package $package,
        FormatterInterface $formatter,
        TranslatorInterface $fallback = null
    ) {
        $class = $this->class;
        if ($fallback !== null && get_class($fallback) !== $class) {
            throw new RuntimeException(sprintf(
                'Translator fallback class %s does not match Cake\I18n\Translator, try clearing your _cake_core_ cache.',
                get_class($fallback)
            ));
        }

        return new $class($locale, $package, $formatter, $fallback);
    }
}
