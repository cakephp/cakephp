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
 * @since         3.3.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use RuntimeException;

/**
 * Factory to create translators
 */
class TranslatorFactory
{
    /**
     * The class to use for new instances.
     *
     * @var string
     * @psalm-var class-string<\Cake\I18n\Translator>
     */
    protected $class = Translator::class;

    /**
     * Returns a new Translator.
     *
     * @param string $locale The locale code for the translator.
     * @param \Cake\I18n\Package $package The Package containing keys and translations.
     * @param \Cake\I18n\FormatterInterface $formatter The formatter to use for interpolating token values.
     * @param \Cake\I18n\Translator $fallback A fallback translator to use, if any.
     * @throws \Cake\Core\Exception\Exception If fallback class does not match Cake\I18n\Translator
     * @return \Cake\I18n\Translator
     */
    public function newInstance(
        $locale,
        Package $package,
        FormatterInterface $formatter,
        ?Translator $fallback = null
    ) {
        $class = $this->class;
        if ($fallback !== null && get_class($fallback) !== $class) {
            throw new RuntimeException(sprintf(
                'Translator fallback class %s does not match Cake\I18n\Translator, try clearing your _cake_core_ cache',
                get_class($fallback)
            ));
        }

        return new $class($locale, $package, $formatter, $fallback);
    }
}
