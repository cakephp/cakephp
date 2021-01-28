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

/**
 * Translator to translate the message.
 *
 * @internal
 */
class Translator
{
    /**
     * @var string
     */
    public const PLURAL_PREFIX = 'p:';

    /**
     * A fallback translator.
     *
     * @var \Cake\I18n\Translator|null
     */
    protected $fallback;

    /**
     * The formatter to use when translating messages.
     *
     * @var \Cake\I18n\FormatterInterface
     */
    protected $formatter;

    /**
     * The locale being used for translations.
     *
     * @var string
     */
    protected $locale;

    /**
     * The Package containing keys and translations.
     *
     * @var \Cake\I18n\Package
     */
    protected $package;

    /**
     * Constructor
     *
     * @param string $locale The locale being used.
     * @param \Cake\I18n\Package $package The Package containing keys and translations.
     * @param \Cake\I18n\FormatterInterface $formatter A message formatter.
     * @param \Cake\I18n\Translator $fallback A fallback translator.
     */
    public function __construct(
        string $locale,
        Package $package,
        FormatterInterface $formatter,
        ?Translator $fallback = null
    ) {
        $this->locale = $locale;
        $this->package = $package;
        $this->formatter = $formatter;
        $this->fallback = $fallback;
    }

    /**
     * Gets the message translation by its key.
     *
     * @param string $key The message key.
     * @return mixed The message translation string, or false if not found.
     */
    protected function getMessage(string $key)
    {
        $message = $this->package->getMessage($key);
        if ($message) {
            return $message;
        }

        if ($this->fallback) {
            $message = $this->fallback->getMessage($key);
            if ($message) {
                $this->package->addMessage($key, $message);

                return $message;
            }
        }

        return false;
    }

    /**
     * Translates the message formatting any placeholders
     *
     * @param string $key The message key.
     * @param array $tokensValues Token values to interpolate into the
     *   message.
     * @return string The translated message with tokens replaced.
     */
    public function translate(string $key, array $tokensValues = []): string
    {
        if (isset($tokensValues['_count'])) {
            $message = $this->getMessage(static::PLURAL_PREFIX . $key);
            if (!$message) {
                $message = $this->getMessage($key);
            }
        } else {
            $message = $this->getMessage($key);
            if (!$message) {
                $message = $this->getMessage(static::PLURAL_PREFIX . $key);
            }
        }

        if (!$message) {
            // Fallback to the message key
            $message = $key;
        }

        // Check for missing/invalid context
        if (is_array($message) && isset($message['_context'])) {
            $message = $this->resolveContext($key, $message, $tokensValues);
            unset($tokensValues['_context']);
        }

        if (empty($tokensValues)) {
            // Fallback for plurals that were using the singular key
            if (is_array($message)) {
                return array_values($message + [''])[0];
            }

            return $message;
        }

        // Singular message, but plural call
        if (is_string($message) && isset($tokensValues['_singular'])) {
            $message = [$tokensValues['_singular'], $message];
        }

        // Resolve plural form.
        if (is_array($message)) {
            $count = $tokensValues['_count'] ?? 0;
            $form = PluralRules::calculate($this->locale, (int)$count);
            $message = $message[$form] ?? (string)end($message);
        }

        if (strlen($message) === 0) {
            $message = $key;
        }

        unset($tokensValues['_count'], $tokensValues['_singular']);

        return $this->formatter->format($this->locale, $message, $tokensValues);
    }

    /**
     * Resolve a message's context structure.
     *
     * @param string $key The message key being handled.
     * @param array $message The message content.
     * @param array $vars The variables containing the `_context` key.
     * @return string|array
     */
    protected function resolveContext(string $key, array $message, array $vars)
    {
        $context = $vars['_context'] ?? null;

        // No or missing context, fallback to the key/first message
        if ($context === null) {
            if (isset($message['_context'][''])) {
                return $message['_context'][''] === '' ? $key : $message['_context'][''];
            }

            return current($message['_context']);
        }
        if (!isset($message['_context'][$context])) {
            return $key;
        }
        if ($message['_context'][$context] === '') {
            return $key;
        }

        return $message['_context'][$context];
    }

    /**
     * Returns the translator package
     *
     * @return \Cake\I18n\Package
     */
    public function getPackage(): Package
    {
        return $this->package;
    }
}
