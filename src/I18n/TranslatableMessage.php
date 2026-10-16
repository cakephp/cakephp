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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\I18n\Exception\I18nException;

/**
 * Holds a message and its translation metadata until a consumer needs text.
 *
 * Construction never translates or caches a locale-specific result. Use this
 * value in PHP attributes or reusable configuration, then call translate() when
 * displaying it. Translations are plain text; consumers remain responsible for
 * escaping the result for their output context.
 *
 * @since 6.0.0
 */
final readonly class TranslatableMessage
{
    /**
     * Store a source message, formatting arguments, translation domain, and context.
     *
     * @param string $message Source message to translate; use a literal for catalog extraction.
     * @param array<array-key, mixed> $arguments CakePHP message-format arguments.
     * @param string $domain Translation domain.
     * @param string $context Translation context.
     */
    public function __construct(
        public string $message,
        public array $arguments = [],
        public string $domain = 'default',
        public string $context = '',
    ) {
    }

    /**
     * Translate with the active locale or an explicit locale without changing it.
     *
     * @param string|null $locale Locale to use, or null for the active locale.
     * @return string Translated and formatted text, without HTML escaping.
     */
    public function translate(?string $locale = null): string
    {
        if ($this->message === '') {
            return '';
        }

        $translator = I18n::translators()->get($this->domain, $locale);
        if ($translator === null) {
            throw new I18nException(sprintf('Translator for domain `%s` could not be found.', $this->domain));
        }

        return $translator->translate($this->message, ['_context' => $this->context] + $this->arguments);
    }
}
