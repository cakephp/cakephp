<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 */
namespace Cake\I18n;

use Aura\Intl\Translator as BaseTranslator;

/**
 * Provides missing message behavior for CakePHP internal message formats.
 *
 * @internal
 */
class Translator extends BaseTranslator
{
    /**
     * @var string
     */
    public const PLURAL_PREFIX = 'p:';

    /**
     * Translates the message formatting any placeholders
     *
     * @param string $key The message key.
     * @param array $tokensValues Token values to interpolate into the
     *   message.
     * @return string The translated message with tokens replaced.
     * @psalm-suppress ParamNameMismatch
     */
    public function translate($key, array $tokensValues = []): string
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
        if (isset($message['_context'])) {
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
}
