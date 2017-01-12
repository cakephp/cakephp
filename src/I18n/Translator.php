<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 *
 * This file contains sections from the Aura Project
 * @license https://github.com/auraphp/Aura.Intl/blob/3.x/LICENSE
 *
 * The Aura Project for PHP.
 *
 * @package Aura.Intl
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Cake\I18n;

use Aura\Intl\FormatterInterface;
use Aura\Intl\TranslatorInterface;

/**
 * Provides missing message behavior for CakePHP internal message formats.
 *
 * @internal
 */
class Translator implements TranslatorInterface
{
    /**
     * A fallback translator.
     *
     * @var \Aura\Intl\TranslatorInterface
     */
    protected $fallback;

    /**
     * The formatter to use when translating messages.
     *
     * @var \Aura\Intl\FormatterInterface
     */
    protected $formatter;

    /**
     * The locale being used for translations.
     *
     * @var string
     */
    protected $locale;

    /**
     * The message keys and translations.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Constructor
     *
     * @param string $locale The locale being used.
     * @param array $messages The message keys and translations.
     * @param \Aura\Intl\FormatterInterface $formatter A message formatter.
     * @param \Aura\Intl\TranslatorInterface|null $fallback A fallback translator.
     */
    public function __construct(
        $locale,
        array $messages,
        FormatterInterface $formatter,
        TranslatorInterface $fallback = null
    ) {
        $this->locale = $locale;
        $this->messages = $messages;
        $this->formatter = $formatter;
        $this->fallback = $fallback;
    }

    /**
     * Gets the message translation by its key.
     *
     * @param string $key The message key.
     * @return string|bool The message translation string, or false if not found.
     */
    protected function getMessage($key)
    {
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        if ($this->fallback) {
            // get the message from the fallback translator
            $message = $this->fallback->getMessage($key);
            // speed optimization: retain locally
            $this->messages[$key] = $message;
            // done!
            return $message;
        }

        // no local message, no fallback
        return false;
    }

    /**
     * Translates the message formatting any placeholders
     *
     *
     * @param string $key The message key.
     * @param array $tokensValues Token values to interpolate into the
     *   message.
     * @return string The translated message with tokens replaced.
     */
    public function translate($key, array $tokensValues = [])
    {
        $message = $this->getMessage($key);

        if (!$message) {
            // Fallback to the message key
            $message = $key;
        }

        // Check for missing/invalid context
        if (isset($message['_context'])) {
            $context = isset($tokensValues['_context']) ? $tokensValues['_context'] : null;
            unset($tokensValues['_context']);

            // No or missing context, fallback to the key/first message
            if ($context === null) {
                $message = current($message['_context']);
            } elseif (!isset($message['_context'][$context])) {
                $message = $key;
            } elseif (!isset($message['_context'][$context])) {
                $message = $key;
            } else {
                $message = $message['_context'][$context];
            }
        }

        if (!$tokensValues) {
            // Fallback for plurals that were using the singular key
            if (is_array($message)) {
                return array_values($message + [''])[0];
            }

            return $message;
        }

        return $this->formatter->format($this->locale, $message, $tokensValues);
    }
}
